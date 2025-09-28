<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    const GCASH = 'gcash';
    const CASH = 'cash';

    protected $guarded = ['id'];

    public function order_payments()
    {
        return $this->hasMany(OrderPayment::class, 'payment_method_id');
    }
}
