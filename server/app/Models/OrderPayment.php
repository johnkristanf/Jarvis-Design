<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderPayment extends Model
{
    const IN_REVIEW = 'in_review';
    const PARTIALLY_PAID = 'partially_paid';
    const FULLY_PAID = 'fully_paid';

    protected $guarded = ['id'];

    protected $casts = [
        'amount_applied' => 'float', // or 'decimal:2' for precision
    ];
   

    public function orders()
    {
        return $this->belongsTo(Orders::class, 'order_id');
    }

    public function payment_methods()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function payment_attachments()
    {
        return $this->hasOne(PaymentAttachment::class, 'order_payment_id');
    }
}
