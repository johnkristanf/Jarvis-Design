<?php

namespace App\Services;

use App\Models\Colors;
use App\Models\Designs;
use App\Models\PreferredDesign;
use App\Models\Sizes;
use App\Models\UploadedDesign;
use Carbon\Carbon;
use Illuminate\Database\QueryException; // Example: Specific database exception
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception; // For catching general exceptions if needed
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DesignsService
{
    public function allDesigns()
    {
        try {
            $designs = Designs::select('id', 'name', 'price', 'quantity', 'image_path')->get();
            return $designs;
        } catch (QueryException $e) {
            Log::error('Error fetching all designs: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
        } catch (Exception $e) {
            Log::error('An unexpected error occurred while fetching designs: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
        }
    }

    public function allColors()
    {
        try {
            $colors = Colors::select('id', 'name')->get();
            return $colors;
        } catch (QueryException $e) {
            Log::error('Error fetching all colors: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
        } catch (Exception $e) {
            Log::error('An unexpected error occurred while fetching colors: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
        }
    }


    public function allSizes()
    {
        try {
            $sizes = Sizes::select('id', 'name')->get();
            return $sizes;
        } catch (QueryException $e) {
            Log::error('Error fetching all sizes: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
        } catch (Exception $e) {
            Log::error('An unexpected error occurred while fetching sizes: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
        }
    }


    public function saveUploadedDesign(string $path, $quantity, $colorID, $sizeID): int
    {
        try {
            
            $preferredDesignID = UploadedDesign::create([
                'path' => $path,
                'quantity' => $quantity,
                'color_id' => $colorID,
                'size_id' => $sizeID,
                'user_id' => Auth::user()->id
            ])->id;

            return $preferredDesignID;

        } catch (QueryException $e) {
            Log::error('Error saving preferred design: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return -1;

        } catch (Exception $e) {
            Log::error('An unexpected error occurred while saving preferred design: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return -1;

        }
    }


    public function allUploadedDesigns()
    {
        try {
            $query = UploadedDesign::with([
                'color:id,name',
                'size:id,name'
            ])
            ->select(
                'id',
                'path',
                'price',
                'quantity',
                'status',
                'size_id',
                'color_id',
                'user_id',
                'created_at'
            );

            $query->when(!Auth::user()->isAdmin(), function ($q) {
                $q->where('user_id', Auth::id());
            });
    
            $results = $query->get();


            // Generate a temporary URL for each design
            $results->transform(function ($item) {
                $item->temp_url = Storage::disk('s3')->temporaryUrl(
                    $item->path,
                    Carbon::now()->addMinutes(10)
                );
                return $item;
            });
    
            return $results;
    
        } catch (QueryException $e) {
            Log::error("Database Query Failed: " . $e->getMessage());
    
            return response()->json([
                'error' => 'Failed to retrieve preferred designs.',
                'message' => $e->getMessage(), 
            ], 500); 

        } catch (\Exception $e) {
            Log::error("An unexpected error occurred: " . $e->getMessage());
    
            return response()->json([
                'error' => 'An unexpected error occurred.',
                'message' => $e->getMessage(), 
            ], 500);
        }
    }

    public function updateUploadedDesign($designID, $status, $price): int
    {
        try {
            
            $design = UploadedDesign::find($designID);

            $design->status = $status;
            $design->price = $price;
            $design->save();

            return $design->id;


        } catch (QueryException $e) {
            Log::error('Error saving preferred design: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return -1;

        } catch (Exception $e) {
            Log::error('An unexpected error occurred while saving preferred design: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return -1;

        }
    }
}