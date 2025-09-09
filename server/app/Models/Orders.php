<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Orders extends Model
{
    const PENDING      = 'pending';
    const FOR_DELIVERY = 'for_delivery';
    const FOR_PICKUP = 'for_pickup';
    const COMPLETED    = 'completed';
    const CANCELLED    = 'cancelled';


    protected $guarded = ['id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }


    public function product(): BelongsTo
    {
        return $this->belongsTo(Products::class, 'product_id');
    }

    public function sizes(): BelongsToMany
    {
        return $this->belongsToMany(Sizes::class, 'order_quantity_size', 'order_id', 'size_id')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function order_tracking(): HasMany
    {
        return $this->hasMany(OrderTracking::class, 'order_id');
    }
}
