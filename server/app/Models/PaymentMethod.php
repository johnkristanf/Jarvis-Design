<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $guarded = ['id'];

    public function order_payments()
    {
        return $this->hasMany(OrderPayment::class, 'payment_method_id');
    }
}
