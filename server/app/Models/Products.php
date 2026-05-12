<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Products extends Model
{
    use SoftDeletes;
    
    protected $guarded = ['id'];

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'product_id');
    }

    public function design_category(): BelongsTo
    {
        return $this->belongsTo(DesignCategory::class, 'category_id');
    }

    public function fabric_type(): BelongsTo
    {
        return $this->belongsTo(Materials::class, 'fabric_type_id'); // Fabric is now in materials due to panel request
    }

    public function designs(): HasMany
    {
        return $this->hasMany(Designs::class, 'product_id');
    }
}
