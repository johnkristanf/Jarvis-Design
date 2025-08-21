<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Sizes extends Model
{
    protected $fillable = [
        'name',
    ];

    // public function preferred_design(): HasOne
    // {
    //     return $this->hasOne(PreferredDesign::class);
    // }

    public function orders()
    {
        return $this->belongsToMany(Orders::class, 'order_quantity_size', 'size_id', 'order_id')
            ->withPivot('quantity')
            ->withTimestamps();
    }
}
