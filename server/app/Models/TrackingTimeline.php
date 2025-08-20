<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrackingTimeline extends Model
{
    protected $guarded = ['id'];

    public function order_tracking(): BelongsTo
    {
        return $this->belongsTo(
            related: OrderTracking::class,
            foreignKey: 'order_tracking_id'
        );
    }
}
