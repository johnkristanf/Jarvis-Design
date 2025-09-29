<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class PaymentAttachment extends Model
{
    protected $guarded = ['id'];


    // TRANSFORM PAYMENT ATTACHMENT URL TO S3 TEMP URL
    protected $appends = ['temp_url'];

    public function getTempUrlAttribute(): ?string
    {
        return Storage::disk('s3')->temporaryUrl(
            $this->url,
            now()->addMinutes(5)
        );
    }

    public function order_payments()
    {
        return $this->belongsTo(OrderPayment::class, 'order_payment_id');
    }
}
