<?php

namespace App\Http\Controllers;

use App\Models\DesignCategory;
use App\Models\Designs;
use App\Models\Materials;
use App\Models\Products;
use App\Models\UploadedDesign;
use App\OrderType;
use App\Services\DesignsService;
use App\Traits\HandleAttachments;
use Illuminate\Http\Request;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DesignsController extends Controller
{
    use HandleAttachments;
    protected $designsService;

    public function __construct(DesignsService $designsService)
    {
        $this->designsService = $designsService;
    }

    private function s3TemporaryUrl(string $path, int $minutes = 10): ?string
    {
        /** @var FilesystemAdapter $s3 */
        $s3 = Storage::disk('s3');

        if (!$s3->exists($path)) {
            return null;
        }

        return $s3->temporaryUrl($path, now()->addMinutes($minutes));
    }

    /**
     * Public endpoint: fetch products with their designs (signed URLs) for customer-facing pages.
     * Query params:
     * - limit: number of products to return (default 6)
     */
    public function getProductsWithDesigns(Request $request)
    {
        $limit = (int) $request->get('limit', 6);
        $limit = $limit > 0 ? $limit : 6;

        $products = Products::select('id', 'name', 'unit_price', 'category_id')
            ->with([
                'design_category:id,name',
                'designs:id,product_id,image_url',
            ])
            ->latest()
            ->take($limit)
            ->get();

        $products->transform(function ($product) {
            $designImages = $product->designs
                ->map(function ($design) {
                    if ($design->image_url) {
                        $tempUrl = $this->s3TemporaryUrl($design->image_url, 10);
                        if (!$tempUrl) {
                            return null;
                        }
                        return [
                            'id' => $design->id,
                            'image_url' => $design->image_url,
                            'temp_url' => $tempUrl,
                        ];
                    }
                    return null;
                })
                ->filter()
                ->values();

            $product->design_images = $designImages;
            unset($product->designs);

            return $product;
        });

        return response()->json($products, 200);
    }

    public function getPreMadeDesigns($sort, $categories = '')
    {
        $categoriesArray = $categories ? explode(',', $categories) : [];

        $result = DesignCategory::with([
                'productStyles',
                'products' => function ($query) {
                    $query->with([
                        'fabric_type:id,name',
                        // Eager load designs and only select fields needed (including image_path/url)
                        'designs'
                    ]);
                }
            ])
            ->select('id', 'name', 'is_fixed_priced', 'fixed_price')
            ->get();

        // Transform the designs' image_path/image_url to temp_url of S3
        $result->each(function ($category) {
            $category->products->each(function ($product) {
                if (isset($product->designs) && $product->designs->count()) {
                    $product->designs->transform(function ($design) {
                        if (!empty($design->image_url)) {
                            // Set temp_url using S3 temporary URL for `image_path`
                            $design->temp_url = $this->s3TemporaryUrl($design->image_url, 60);
                        } else {
                            $design->temp_url = null;
                        }
                        return $design;
                    });
                }
            });
        });

        return response()->json($result, 200);
    }

    public function getAllDesigns()
    {
        $designs = $this->designsService->allDesigns();

        return response()->json($designs, 200);
    }

    public function getAllProducts(Request $request)
    {

        $limit = $request->get('limit', 5);
        $products = Products::select('id', 'name', 'unit_price', 'category_id', 'fabric_quantity')
            ->with([
                'design_category:id,name',
                'designs:id,product_id,image_url',
            ])
            ->latest()
            ->paginate($limit);

        // Map each product and generate signed URLs for designs
        $products->getCollection()->transform(function ($product) {
            // Generate temporary URLs for each design's image
            $designImages = $product->designs->map(function ($design) {
                if ($design->image_url) {
                    $tempUrlString = $this->s3TemporaryUrl($design->image_url, 10);
                    if (!$tempUrlString) {
                        return null;
                    }

                    return [
                        'id' => $design->id,
                        'image_url' => $design->image_url,
                        'temp_url' => $tempUrlString
                    ];
                }
                return null;
            })
                ->filter()
                ->values(); // Remove nulls if image doesn't exist

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
                $design->temp_url = $this->s3TemporaryUrl($design->image_url, 10);

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
            $tempUrl = $this->s3TemporaryUrl($filePath, 60);
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
            'is_pocket_included' => 'nullable|boolean',
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
            'is_pocket_included' => $request->has('is_pocket_included') ? filter_var($request->is_pocket_included, FILTER_VALIDATE_BOOLEAN) : false,
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
        $validated = $request->validate([
            'design' => 'required',
            'product_id' => 'required',
            'product_name' => 'required|string',
            'category_name' => 'required|string',
        ]);

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

            // Save record in the 'designs' table
            Designs::create([
                'image_url' => $s3Key, // Store the S3 object key
                'product_id' => $validated['product_id'],
            ]);
        }

        return response()->json([
            'message' => 'Product design uploaded successfully!',
        ], 201);
    }

    public function deleteProductDesign(string $imageURL)
    {
        Log::info("imageURL: ", [$imageURL]);
        $decodedPath = urldecode($imageURL);

        // Delete the file from your configured S3 disk
        if (Storage::disk('s3')->exists($decodedPath)) {
            Storage::disk('s3')->delete($decodedPath);
            return response()->json(['message' => 'Design deleted successfully.'], 200);
        }

        return response()->json(['error' => 'File not found.'], 404);
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

    /**
     * Save an AI-generated design image to S3.
     * S3 path: ai_generated_designs/{user_id}/{unique_filename}
     */
    public function saveAiGeneratedDesign(Request $request)
    {
        try {
            $request->validate([
                'ai_design_file' => 'required|file|mimes:jpg,jpeg,png,webp|max:10240',
            ]);

            $userId = Auth::id();
            $s3Key = $this->uploadToS3(
                root: 'ai_generated_designs',
                sub: $userId,
                file: $request->file('ai_design_file')
            );

            return response()->json([
                'success' => true,
                'message' => 'AI design saved successfully',
                's3_key'  => $s3Key,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error saving AI design: ' . $e->getMessage());
            return response()->json([
                'error'   => true,
                'message' => 'Failed to save AI design',
            ], 500);
        }
    }

    /**
     * Retrieve all saved AI-generated designs for the authenticated user.
     * Returns an array of { s3_key, temp_url } objects.
     */
    public function getSavedAiDesigns()
    {
        try {
            $userId = Auth::id();
            $prefix = "ai_generated_designs/{$userId}";
            $files  = Storage::disk('s3')->files($prefix);

            $designs = array_map(function ($filePath) {
                return [
                    's3_key'   => $filePath,
                    'temp_url' => $this->s3TemporaryUrl($filePath, 10),
                ];
            }, $files);

            return response()->json($designs, 200);
        } catch (\Exception $e) {
            Log::error('Error fetching saved AI designs: ' . $e->getMessage());
            return response()->json([
                'error'   => true,
                'message' => 'Failed to fetch saved AI designs',
            ], 500);
        }
    }
}
