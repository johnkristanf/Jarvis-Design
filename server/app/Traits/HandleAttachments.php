<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;

trait HandleAttachments
{
    public function uploadToS3($root, $sub, $file): string
    {
        $extractedFileName = $file->getClientOriginalName();
        $fileContent = file_get_contents($file->getPathname());

        // Create unique filename to avoid conflicts
        $uniqueFileName = uniqid().'_'.basename($extractedFileName);
        $s3Key = "{$root}/{$sub}/{$uniqueFileName}";

        // Upload to S3
        Storage::disk('s3')->put($s3Key, $fileContent, [
            'visibility' => 'private',
        ]);

        return $s3Key;
    }
}
