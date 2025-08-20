<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderTracking extends Model
{
    protected $guarded = ['id'];

    public function orders()
    {
        return $this->belongsTo(
            related: Orders::class,
            foreignKey: 'order_id'
        );
    }

    public function tracking_map_location(): HasMany
    {
        return $this->hasMany(
            related: TrackingMapLocation::class,
            foreignKey: 'order_tracking_id'
        );
    }

    public function tracking_timeline(): HasMany
    {
        return $this->hasMany(
            related: TrackingTimeline::class,
            foreignKey: 'order_tracking_id'
        );
    }
}
