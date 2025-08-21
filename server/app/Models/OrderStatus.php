<?php

namespace App\Models;

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
