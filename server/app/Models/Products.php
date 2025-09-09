<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Products extends Model
{
    protected $guarded = ['id'];

    public function products()
    {
        return $this->hasMany(Orders::class, 'product_id');
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
