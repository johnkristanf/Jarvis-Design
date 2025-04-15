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


    public function savePreferredDesign(string $path): int
    {
        try {
            
            $preferredDesignID = PreferredDesign::create([
                'path' => $path,
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
}