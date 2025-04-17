<?php

namespace App\Http\Controllers;

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
                $preferredDesignID = $this->designsService->saveUploadedDesign($s3Key, $quantity, $colorId, $sizeId);
                
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
}
