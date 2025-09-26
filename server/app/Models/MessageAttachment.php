<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class MessageAttachment extends Model
{
    protected $guarded = ['id'];

    protected $appends = ['attachment_temp_url'];

    public function getAttachmentTempUrlAttribute(): ?string
    {
        if (! $this->attachment_url) {
            return null;
        }

        return Storage::disk('s3')->temporaryUrl(
            $this->attachment_url,
            now()->addMinutes(5)
        );
    }
    public function messages()
    {
        return $this->belongsTo(Message::class, 'message_id');
    }
}
