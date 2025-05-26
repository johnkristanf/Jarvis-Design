<?php

namespace App\Http\Controllers;

use App\Models\DesignCategory;
use App\Models\Designs;
use App\Models\PreferredDesign;
use App\Models\UploadedDesign;
use App\OrderType;
use App\Services\DesignsService;
use Aws\S3\S3Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $query = Designs::query();

        // Apply category filter
        if (!empty($categoriesArray)) {
            $query->whereIn('category_id', $categoriesArray);
        }

        // Apply sort
        switch ($sort) {
            case 'high_low':
                $query->orderBy('price', 'desc');
                break;
            case 'low_high':
                $query->orderBy('price', 'asc');
                break;
            default: // newest
                $query->orderBy('created_at', 'desc');
                break;
        }

        $designs = $query->get()->transform(function ($design) {
            $design->image_path = Storage::disk('s3')->temporaryUrl(
                $design->image_path,
                now()->addMinutes(60) // You can adjust the expiry time
            );

            return $design;
        });

        return response()->json($designs);
    }



    public function getAllDesigns()
    {
        $designs = $this->designsService->allDesigns();
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

            $uploadedFiles  = $request->file('multi_file_upload');

            Log::info("uploadedFiles: ", [
                'uploadedFiles' => $uploadedFiles
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
                        'visibility' => 'private'
                    ]);

                    $uploadedFilePaths[] = $s3Key;
                } catch (\Exception $e) {
                    $failedUploads[] = $extractedFileName ?? "File {$index}";
                    Log::error("Individual file upload failed: " . $e->getMessage());
                }
            }

            if (empty($uploadedFilePaths)) {
                return response()->json([
                    'error' => true,
                    'msg' => 'All file uploads failed',
                    'failed_files' => $failedUploads
                ], 500);
            }

            return response()->json([
                'success' => true,
                'preferred_design_id' => $preferredDesignID,
                'uploaded_files' => $uploadedFilePaths,
                'total_uploaded' => count($uploadedFilePaths),
                'failed_files' => $failedUploads
            ], 200);
        } catch (\Exception $e) {

            Log::error("Error in Upload Preferred Design: ", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => true,
                'msg'   => 'Error occured in uploading preferred design'
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
        Log::info("designID: " . $designID);

        $prefix = "uploads/{$designID}";
        $files = Storage::disk('s3')->files($prefix);

        Log::info("files: ", [
            'files' => $files
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

        Log::info("Design Uploaded Data: ", [
            'status' => $status,
            'price' => $price,
            'designID' => $designID,
        ]);

        $updatedUploadedDesignID = $this->designsService->updateUploadedDesign($designID, $status, $price);

        return response()->json([
            'message' => 'Design updated successfully',
            'design_od' => $updatedUploadedDesignID
        ]);
    }

    public function addPreMadeDesigns(Request $request)
    {
        $category_id = $request->input('category_id');
        $name = $request->input('name');
        $price = $request->input('price');
        $quantity = $request->input('quantity');

        $file = $request->file('file');


        $extractedFileName = $file->getClientOriginalName();
        $file = file_get_contents($file->getPathname());


        $uniqueFileName = uniqid() . '_' . basename($extractedFileName);
        $s3Key = "pre_made/" . $uniqueFileName;


        // S3 UPLOAD FACADE
        $isUploaded = Storage::disk('s3')->put($s3Key, $file, [
            'visibility' => 'private'
        ]);

        if ($isUploaded) {
            $createdDesignId = Designs::create([
                'name' => $name,
                'price' => $price,
                'quantity' => -1, // UP FOR DECIDING IF THE QUANTITY IS INCLUDED FOR NOW WE PUT DUMMY VALUES TO NOT GET ERROR
                'image_path' => $s3Key,
                'category_id' => $category_id,
            ]);

            return response()->json(['created_design_id' => $createdDesignId]);
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

        Log::info("Design Data: ", [
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

        if (!$design) {
            Log::error("Error in finding design related tables");
        }

        Log::info("Selected Design: ", [
            'design' => $design
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
        $categories = DesignCategory::select('id', 'name')->get();
        return response()->json($categories);
    }
}
