<?php

namespace App\Http\Controllers;

use App\Models\PreferredDesign;
use App\Services\DesignsService;
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

    public function uploadPreferredDesign(Request $request)
    {

        $request->validate([
            'file' => 'required|file|max:10240', // Max 10MB
        ]);

        try {

            $uploadedFileData = $request->file('file');
            $colorId = $request->input('color');
            $sizeId = $request->input('size');


            $extractedFileName = $uploadedFileData->getClientOriginalName();

            $s3Key = "uploads/" . basename($extractedFileName);
            $file = file_get_contents($uploadedFileData->getPathname());

            Log::info("File Data: ", [
                'sizeId' => $sizeId,
                'colorId' => $colorId,
                'extractedFileName' => $extractedFileName,
            ]);

            $isUploaded = Storage::disk('s3')->put($s3Key, $file, [
                'visibility' => 'private'
            ]);
                       
            // STILL ERROR DLI MAKA UPLOAD SA S3 I AS IS NALANG SA NATO NA UPLOADED PARA MAKA PROCEED NA
            // THE REAL CODE:
            // if ($isUploaded) {
            //     $preferredDesignID = $this->designsService->savePreferredDesign($s3Key);
                
            //     return response()->json([
            //         'success' => true,
            //         'preferred_design_id' => $preferredDesignID
            //     ], 200);
           
            // }
            
            // return response()->json([
            //     'error' => true,
            //     'message' => "No File Uploaded to S3 Bucket"
            // ], 500);


            // THE AS IS CODE:
                $preferredDesignID = $this->designsService->savePreferredDesign($s3Key, $colorId, $sizeId);
                
                return response()->json([
                    'success' => true,
                    'preferred_design_id' => $preferredDesignID
                ], 200);
           

        } catch (\Exception $e) {

            Log::error("Error in Upload Preferred Design: ", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),

                // 'aws_message' => method_exists($e, 'getAwsErrorMessage') ? $e->getAwsErrorMessage() : 'N/A',
                // 'aws_code' => method_exists($e, 'getAwsErrorCode') ? $e->getAwsErrorCode() : 'N/A',
            ]);

            return response()->json([
                'error' => true,
                'msg'   => 'Error occured in uploading preferred design'
            ], 500);
        }
    }

    public function getUploadedDesigns()
    {
       
        $results = $this->designsService->allPreferredDesigns();
        return response()->json($results, 200);
   
    }

    public function updateUploadedDesigns(Request $request)
    {
        $validated = $request->validate([
            'status' => 'required|string', 
            'price' => 'required|numeric',
            'design_id' => 'required|integer|exists:designs,id',
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
