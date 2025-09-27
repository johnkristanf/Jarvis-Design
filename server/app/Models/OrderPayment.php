<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderPayment extends Model
{
    protected $guarded = ['id'];

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
        return $this->hasMany(PaymentAttachment::class, 'order_payment_id');
    }
}
