<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Orders extends Model
{
    protected $guarded = ['id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function order_status(): BelongsTo
    {
        return $this->belongsTo(OrderStatus::class, 'status_id');
    }


    public function sizes(): BelongsToMany
    {
        return $this->belongsToMany(Sizes::class, 'order_quantity_size', 'order_id', 'size_id')
            ->withPivot('quantity')
            ->withTimestamps();
    }
}
