<?php

namespace App\Http\Controllers;

use App\Models\DesignCategory;
use App\Models\Designs;
use App\Models\Materials;
use App\Models\Products;
use App\Models\UploadedDesign;
use App\OrderType;
use App\Services\DesignsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DesignsController extends Controller
{
    protected $designsService;

    public function __construct(DesignsService $designsService)
    {
        $this->designsService = $designsService;
    }

    public function getPreMadeDesigns($sort, $categories = '')
    {
        $categoriesArray = $categories ? explode(',', $categories) : [];

        $result = DesignCategory::with('products.fabric_type:id,name') // eager load category
            ->select('id', 'name', 'is_fixed_priced', 'fixed_price')
            ->get();

        return response()->json($result, 200);

        // Apply category filter
        // if (!empty($categoriesArray)) {
        //     $query->whereIn('category_id', $categoriesArray);
        // }

        // // Apply sort
        // switch ($sort) {
        //     case 'high_low':
        //         $query->orderBy('price', 'desc');
        //         break;
        //     case 'low_high':
        //         $query->orderBy('price', 'asc');
        //         break;
        //     default: // newest
        //         $query->orderBy('created_at', 'desc');
        //         break;
        // }

        // $designs = $query->get()->transform(function ($design) {
        //     $design->image_path = Storage::disk('s3')->temporaryUrl(
        //         $design->image_path,
        //         now()->addMinutes(60) // You can adjust the expiry time
        //     );

        //     return $design;
        // });

        // $grouped = $designs
        //     ->groupBy(fn($design) => $design->design_categories->name ?? 'Uncategorized')
        //     ->map(function ($group) {
        //         return $group->groupBy('tag');
        //     });

        // return response()->json($grouped);
    }

    public function getAllDesigns()
    {
        $designs = $this->designsService->allDesigns();

        return response()->json($designs, 200);
    }

    public function getAllProducts()
    {
        $products = Products::select('id', 'name', 'unit_price', 'category_id', 'fabric_quantity')
            ->with([
                'design_category:id,name',
                'designs:id,product_id,image_url',
            ])
            ->latest()
            ->get();


        // Map each product and generate signed URLs for designs
        $products = $products->map(function ($product) {
            // Generate temporary URLs for each design's image
            $designImages = $product->designs->map(function ($design) {
                if ($design->image_url && Storage::disk('s3')->exists($design->image_url)) {
                    return Storage::disk('s3')->temporaryUrl(
                        $design->image_url,
                        now()->addMinutes(10) // 10 minutes validity
                    );
                }
                return null;
            })->filter()->values(); // Remove nulls if image doesn't exist

            // Append design_images to product
            $product->design_images = $designImages;

            // Remove the designs relation if you only want URLs
            unset($product->designs);

            return $product;
        });

        return response()->json($products, 200);
    }

    public function getProductBusinessDesign($product_id)
    {
        $designs = Designs::select('id', 'image_url')
            ->where('product_id', $product_id)
            ->get()
            ->map(function ($design) {
                $design->temp_url = Storage::disk('s3')->temporaryUrl(
                    $design->image_url,              // key on S3
                    now()->addMinutes(10)            // expires in 10 minutes
                );

                return $design;
            });

        return response()->json($designs, 200);
    }

    public function getAllColors()
    {
        $colors = $this->designsService->allColors();

        return response()->json($colors, 200);
    }

    public function getAllSizes()
    {
        $sizes = $this->designsService->allSizes();

        return response()->json($sizes, 200);
    }

    public function uploadDesign(Request $request)
    {

        try {

            $request->validate([
                'multi_file_upload' => 'required|array',
                'multi_file_upload.*' => 'file|max:10240', // each file max 10MB
            ]);

            $uploadedFiles = $request->file('multi_file_upload');

            Log::info('uploadedFiles: ', [
                'uploadedFiles' => $uploadedFiles,
            ]);

            $orderOption = $request->input('order_option');
            $colorId = $request->input('color');
            $sizeId = $request->input('size');
            $quantity = $request->input('quantity');

            // SAVE DESIGN DATA TO THE DATABASE
            $preferredDesignID = $this->designsService->saveUploadedDesign($orderOption, $quantity, $colorId, $sizeId);

            $uploadedFilePaths = [];
            $failedUploads = [];

            foreach ($uploadedFiles as $index => $file) {
                try {
                    $extractedFileName = $file->getClientOriginalName();
                    $fileContent = file_get_contents($file->getPathname());

                    // Create unique filename to avoid conflicts
                    $uniqueFileName = uniqid() . '_' . basename($extractedFileName);
                    $s3Key = "uploads/{$preferredDesignID}/{$uniqueFileName}";

                    // Upload to S3
                    Storage::disk('s3')->put($s3Key, $fileContent, [
                        'visibility' => 'private',
                    ]);

                    $uploadedFilePaths[] = $s3Key;
                } catch (\Exception $e) {
                    $failedUploads[] = $extractedFileName ?? "File {$index}";
                    Log::error('Individual file upload failed: ' . $e->getMessage());
                }
            }

            if (empty($uploadedFilePaths)) {
                return response()->json([
                    'error' => true,
                    'msg' => 'All file uploads failed',
                    'failed_files' => $failedUploads,
                ], 500);
            }

            return response()->json([
                'success' => true,
                'preferred_design_id' => $preferredDesignID,
                'uploaded_files' => $uploadedFilePaths,
                'total_uploaded' => count($uploadedFilePaths),
                'failed_files' => $failedUploads,
            ], 200);
        } catch (\Exception $e) {

            Log::error('Error in Upload Preferred Design: ', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => true,
                'msg' => 'Error occured in uploading preferred design',
            ], 500);
        }
    }

    public function getUploadedDesigns()
    {
        try {
            $results = $this->designsService->allUploadedDesigns();

            return response()->json($results, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve preferred designs.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function getUploadedDesignByID($designID)
    {
        Log::info('designID: ' . $designID);

        $prefix = "uploads/{$designID}";
        $files = Storage::disk('s3')->files($prefix);

        Log::info('files: ', [
            'files' => $files,
        ]);

        $urls = [];

        foreach ($files as $filePath) {
            // Create temporary URL valid for 1 hour (60 minutes)
            $tempUrl = Storage::disk('s3')->temporaryUrl($filePath, now()->addMinutes(60));
            $urls[] = [
                'temporary_url' => $tempUrl,
            ];
        }

        return response()->json($urls);
    }

    public function updateUploadedDesigns(Request $request)
    {
        $validated = $request->validate([
            'status' => 'required|string',
            'price' => 'required|numeric',
            'design_id' => 'required|integer',
        ]);

        $status = $validated['status'];
        $price = $validated['price'];
        $designID = $validated['design_id'];

        Log::info('Design Uploaded Data: ', [
            'status' => $status,
            'price' => $price,
            'designID' => $designID,
        ]);

        $updatedUploadedDesignID = $this->designsService->updateUploadedDesign($designID, $status, $price);

        return response()->json([
            'message' => 'Design updated successfully',
            'design_od' => $updatedUploadedDesignID,
        ]);
    }

    // public function addPreMadeDesigns(Request $request)
    // {
    //     $category_id = $request->input('category_id');
    //     $name = $request->input('name');
    //     $price = $request->input('price');
    //     $unitMeasure = $request->input('unitMeasure');
    //     $tag = $request->input('tag');
    //     $description = $request->input('description');

    //     Log::info("New Data: ", [
    //         'unitMeasure' => $unitMeasure,
    //         'tag' => $tag,
    //         'description' => $description,
    //     ]);

    //     $file = $request->file('file');

    //     $extractedFileName = $file->getClientOriginalName();
    //     $file = file_get_contents($file->getPathname());

    //     $uniqueFileName = uniqid() . '_' . basename($extractedFileName);
    //     $s3Key = "pre_made/" . $uniqueFileName;

    //     // S3 UPLOAD FACADE
    //     $isUploaded = Storage::disk('s3')->put($s3Key, $file, [
    //         'visibility' => 'private'
    //     ]);

    //     if ($isUploaded) {
    //         $createdDesignId = Designs::create([
    //             'price' => $price,
    //             'image_path' => $s3Key,
    //             'unit_measure' => $unitMeasure,
    //             'tag' => $tag,
    //             'description' => $description,
    //             'category_id' => $category_id,
    //         ]);

    //         return response()->json(['created_design_id' => $createdDesignId]);
    //     }
    // }

    public function addProduct(Request $request)
    {

        $validatedData = $request->validate([
            'category_id' => 'required|exists:design_categories,id',
            'product_name' => 'required|string|max:255',
            'unit_price' => 'required|numeric|min:0',
            'fabric_type_id' => 'numeric|min:0',
            'fabric_quantity' => 'numeric|min:0',
        ]);

        Log::info('Product Info: ', [
            'data' => $validatedData,
        ]);

        $newProduct = Products::create([
            'name' => $validatedData['product_name'],
            'unit_price' => $validatedData['unit_price'],
            'category_id' => $validatedData['category_id'],
            'fabric_type_id' => $validatedData['fabric_type_id'] ?? null,
            'fabric_quantity' => $validatedData['fabric_quantity'] ?? null,
        ]);

        return response()->json([
            'message' => 'Product created successfully!',
            'product' => $newProduct,
        ], 201);
    }


    public function destroy($id)
    {
        $product = Products::findOrFail($id);
        
        // Loop through related designs and delete their images from S3
        foreach ($product->designs as $design) {
            if ($design->image_url && Storage::disk('s3')->exists($design->image_url)) {
                Storage::disk('s3')->delete($design->image_url);
            }
        }

        // Optionally, delete the designs from DB (if needed)
        $product->designs()->delete();
        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully.',
            'status' => true,
        ], 200);
    }

    public function addProductDesign(Request $request)
    {
        try {

            $validated = $request->validate([
                'design' => 'required',
                'product_id' => 'required',
                'product_name' => 'required|string',
                'category_name' => 'required|string',
            ]);

            Log::info('Design Data: ', $validated);

            if ($request->hasFile('design')) {
                $file = $request->file('design');

                // Generate safe, unique filename
                $categorySlug = Str::slug($validated['category_name']);
                $slugName = Str::slug($validated['product_name']);
                $extractedFileName = $file->getClientOriginalName();

                // Sanitize category name for S3 path
                $s3Key = "designs/{$categorySlug}/{$slugName}/{$extractedFileName}";

                // Upload to S3
                Storage::disk('s3')->put($s3Key, file_get_contents($file), [
                    'visibility' => 'private',
                ]);

                Log::info('Uploaded to S3', ['s3_key' => $s3Key]);

                // Save record in the 'designs' table
                Designs::create([
                    'image_url' => $s3Key, // Store the S3 object key
                    'product_id' => $validated['product_id'],
                ]);
            }

            return response()->json([
                'message' => 'Product design uploaded successfully!',
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Design Upload Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'An error occurred while uploading the design.',
            ], 500);
        }
    }

    public function attachDesignMaterial(Request $request)
    {
        $validated = $request->validate([
            'design_id' => 'required|integer|exists:designs,id',
            'designType' => 'required|string',
            'material_quantity_arr' => 'required|array',
            'material_quantity_arr.*.material_id' => 'required|integer|exists:materials,id',
            'material_quantity_arr.*.quantity_used' => 'required|numeric|min:1',
        ]);

        $designType = $validated['designType'];
        $designID = $validated['design_id'];
        $materials = $validated['material_quantity_arr'];

        Log::info('Design Data: ', [
            'designType' => $designType,
            'designID' => $designID,
            'materials' => $materials,
        ]);

        // switch (strtolower($designType)) {
        //     case 'pre-made':
        //         $design = Designs::with('materials')->find($designID);
        //         break;
        //     case 'uploaded':
        //         $design = UploadedDesign::with('materials')->find($designID);
        //         break;
        //     default:
        //         $design = null;
        // }

        $design = match ($designType) {
            OrderType::PRE_MADE->value => Designs::with('materials')->find($designID),
            OrderType::UPLOADED->value => UploadedDesign::with('materials')->find($designID),
            default => null
        };

        if (! $design) {
            Log::error('Error in finding design related tables');
        }

        Log::info('Selected Design: ', [
            'design' => $design,
        ]);

        foreach ($materials as $material) {
            $materialId = $material['material_id'];
            $quantityUsed = $material['quantity_used'];

            // Save the relationship — adjust model names if needed
            $design->materials()->attach($materialId, ['quantity_used' => $quantityUsed]);
        }

        return response()->json(['message' => 'Materials attached successfully.']);
    }

    public function getDesignCategories()
    {
        $categories = DesignCategory::select('id', 'name', 'is_fixed_priced', 'fixed_price')->get();

        return response()->json($categories);
    }

    public function getFabricTypes()
    {
        $categories = Materials::select('id', 'name', 'unit')->get();

        return response()->json($categories);
    }
}
