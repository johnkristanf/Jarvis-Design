<?php

namespace App\Services;

use App\Models\Colors;
use App\Models\Designs;
use App\Models\PreferredDesign;
use App\Models\Sizes;
use Illuminate\Database\QueryException; // Example: Specific database exception
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception; // For catching general exceptions if needed
use Illuminate\Support\Facades\DB;

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


    public function savePreferredDesign(string $path, $colorID, $sizeID): int
    {
        try {
            
            $preferredDesignID = PreferredDesign::create([
                'path' => $path,
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


    public function allPreferredDesigns()
    {
        try {
            $results = DB::table('preferred_designs')
                ->join('colors', 'preferred_designs.color_id', '=', 'colors.id')
                ->join('sizes', 'preferred_designs.size_id', '=', 'sizes.id')
                ->select(
                    'preferred_designs.id',
                    'preferred_designs.path',
                    'preferred_designs.price',
                    'preferred_designs.status',
                    'preferred_designs.user_id',
                    'colors.name as color_name',
                    'sizes.name as size_name',  
                    'preferred_designs.created_at'
                )
                ->get();
    
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
            
            $design = PreferredDesign::find($designID);

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