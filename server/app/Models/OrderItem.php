<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $guarded = ['id'];
    protected $casts = [
        'selected_styles' => 'array',
    ];

    protected $appends = ['selected_product_styles'];

    public function getSelectedProductStylesAttribute()
    {
        if (!$this->selected_styles) {
            return [];
        }

        return ProductStyle::findMany($this->selected_styles);
    }

    public function order()
    {
        return $this->belongsTo(Orders::class, 'order_id');
    }

    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id');
    }

    public function sizes()
    {
        return $this->belongsToMany(Sizes::class, 'order_quantity_size', 'order_item_id', 'size_id')
            ->withPivot('quantity')
            ->withTimestamps();
    }
}
