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
    const IN_PROGRESS    = 'in_progress';


    protected $guarded = ['id'];


    protected $casts = [
        'total_price' => 'float', 
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }


    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }


    public function order_payments()
    {
        return $this->hasMany(OrderPayment::class, 'order_id');
    }

    public function discount()
    {
        return $this->hasOne(Discount::class, 'order_id');
    }

    // Sizes are now on OrderItem, but if needed we can leave it or remove it.
    // We'll remove it as sizes are per-item now.

}
