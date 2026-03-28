<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    protected $guarded = ['id'];
    protected $casts = [
        'selected_styles' => 'array',
        'customizations' => 'array',
    ];

    protected $appends = ['selected_product_styles'];

    public function getSelectedProductStylesAttribute()
    {
        if (!$this->selected_styles) {
            return [];
        }

        return ProductStyle::findMany($this->selected_styles);
    }


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    
    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id');
    }

   
    public function fabric_types()
    {
        return $this->belongsTo(Materials::class, 'fabric_type_id');
    }

  
    public function size()
    {
        return $this->belongsTo(Sizes::class, 'size_id');
    }
}
