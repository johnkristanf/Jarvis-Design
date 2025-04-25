<?php

namespace App\Http\Controllers;

use App\Models\DesignCategory;
use App\Models\Designs;
use App\Models\PreferredDesign;
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
        Log::info('Sort:', [$sort]);
        Log::info('Categories:', $categoriesArray);

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

        return response()->json($query->get());
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
                'file' => 'required|file|max:10240', // Max 10MB
            ]);

            $uploadedFileData = $request->file('file');
            $orderOption = $request->input('order_option');
            $colorId = $request->input('color');
            $sizeId = $request->input('size');
            $quantity = $request->input('quantity');

            $extractedFileName = $uploadedFileData->getClientOriginalName();
            $file = file_get_contents($uploadedFileData->getPathname());


            $uniqueFileName = uniqid() . '_' . basename($extractedFileName);
            $s3Key = "uploads/" . $uniqueFileName;


            // S3 UPLOAD FACADE
            $isUploaded = Storage::disk('s3')->put($s3Key, $file, [
                'visibility' => 'private'
            ]);

            if ($isUploaded) {
                $preferredDesignID = $this->designsService->saveUploadedDesign($s3Key, $orderOption, $quantity, $colorId, $sizeId);

                return response()->json([
                    'success' => true,
                    'preferred_design_id' => $preferredDesignID
                ], 200);
            }

            return response()->json([
                'error' => true,
                'message' => "No File Uploaded to S3 Bucket"
            ], 500);
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

        $results = $this->designsService->allUploadedDesigns();
        return response()->json($results, 200);
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
            'material_quantity_arr' => 'required|array',
            'material_quantity_arr.*.material_id' => 'required|integer|exists:materials,id',
            'material_quantity_arr.*.quantity_used' => 'required|numeric|min:1',
        ]);

        $designId = $validated['design_id'];
        $materials = $validated['material_quantity_arr'];

        foreach ($materials as $material) {
            $materialId = $material['material_id'];
            $quantityUsed = $material['quantity_used'];

            // Save the relationship — adjust model names if needed
            DB::table('designs_materials')
                ->updateOrInsert(
                    [
                        'design_id' => $designId,
                        'material_id' => $materialId,
                        'created_at' => now(),
                        'updated_at' => now()
                    ],

                    [
                        'quantity_used' => $quantityUsed
                    ]

                );
        }

        return response()->json(['message' => 'Materials attached successfully.']);
    }


    public function getDesignCategories()
    {
        $categories = DesignCategory::select('id', 'name')->get();
        return response()->json($categories);
    }
}
