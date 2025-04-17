<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OrderType extends Model
{
    protected $fillable = [
        'name'
    ];


    // ORDER CLASS HERE
    // public function orders(): HasOne 
    // {
    //     return $this->hasOne();
    // }
}
