<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentAttachment extends Model
{
    protected $guarded = ['id'];

    public function order_payments()
    {
        return $this->belongsTo(OrderPayment::class, 'order_payment_id');
    }
}
