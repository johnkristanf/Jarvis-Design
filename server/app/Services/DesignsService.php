<?php

namespace App\Services;

use App\Models\Colors;
use App\Models\Designs;
use App\Models\OrderType;
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
            $designs = Designs::with('design_categories:id,name') 
                ->select('id', 'name', 'price', 'quantity', 'image_path', 'category_id')
                ->get();

            $designs->transform(function ($design) {
                $design->image_path = Storage::disk('s3')->temporaryUrl(
                    $design->image_path,
                    now()->addMinutes(60) // You can adjust the expiry time
                );
                return $design;
            });

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



    public function saveUploadedDesign($orderOption, $quantity, $colorID, $sizeID): int
    {
        try {

            $preferredDesignID = UploadedDesign::create([
                'order_option' => $orderOption,
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
                    'order_option',
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

            $results = $query->orderBy('created_at', 'desc')->get();

            // Generate a temporary URL for each design
            // $results->transform(function ($item) {
            //     $item->temp_url = Storage::disk('s3')->temporaryUrl(
            //         $item->path,
            //         Carbon::now()->addMinutes(10)
            //     );
            //     return $item;
            // });

            return $results;
        } catch (QueryException $e) {
            Log::error("Database Query Failed: " . $e->getMessage());
            throw $e;
        } catch (\Exception $e) {
            Log::error("An unexpected error occurred: " . $e->getMessage());
            throw $e;
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
