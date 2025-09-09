<?php

namespace App\Models;

use App\Enums\OrderStatus as OrderStatusEnums;
use Illuminate\Database\Eloquent\Model;

class OrderStatus extends Model
{

   
    protected $table = 'order_status';

    protected $fillable = [
        'name',
    ];


    // ORDER CLASS HERE
    // public function orders(): HasOne
    // {
    //     return $this->hasOne();
    // }
}
