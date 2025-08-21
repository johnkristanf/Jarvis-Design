<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Message extends Model
{
    protected $guarded = ['id'];

    protected $appends = ['attachment_temp_url'];

    public function getAttachmentTempUrlAttribute(): ?string
    {
        if (! $this->attachment_url) {
            return null;
        }

        // Generate signed URL (valid for 5 minutes by default)
        return Storage::disk('s3')->temporaryUrl(
            $this->attachment_url,
            now()->addMinutes(5)
        );
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
