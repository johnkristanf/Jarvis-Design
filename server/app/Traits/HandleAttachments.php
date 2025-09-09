<?php

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Storage;

trait HandleAttachments
{
    public function uploadToS3($root, $sub, $file): string
    {
        $extractedFileName = $file->getClientOriginalName();
        $fileContent = file_get_contents($file->getPathname());

        // Create unique filename to avoid conflicts
        $uniqueFileName = uniqid() . '_' . basename($extractedFileName);
        $s3Key = "{$root}/{$sub}/{$uniqueFileName}";

        // Upload to S3
        Storage::disk('s3')->put($s3Key, $fileContent, [
            'visibility' => 'private',
        ]);

        return $s3Key;
    }


    public function deleteS3File($filePath)
    {
        if (!empty($filePath) && Storage::disk('s3')->exists($filePath)) {
            Storage::disk('s3')->delete($filePath);
            return [
                'success' => true,
                'message' => 'S3 File Deleted Successfully'
            ];
        }
    }

    public function transformOrderDesignToS3Temp($orders)
    {
        $collection = ($orders instanceof LengthAwarePaginator || $orders instanceof Paginator)
            ? $orders->getCollection()
            : $orders;

        $collection->transform(function ($order) {
            $filePath = $order->own_design_url ?: $order->business_design_url;

            $order->temp_url = $filePath
                ? Storage::disk('s3')->temporaryUrl($filePath, Carbon::now()->addMinutes(10))
                : null;

            return $order;
        });

        return $orders;
    }
}
