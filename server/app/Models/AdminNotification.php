<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminNotification extends Model
{
    const ORDER_NOTIFICATION_TYPE = 'order';
    const PAYMENT_NOTIFICATION_TYPE = 'payment';
    const STOCK_NOTIFICATION_TYPE = 'stock';

    protected $guarded = ['id'];
}
