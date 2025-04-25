<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Designs extends Model
{
    protected $guarded = ['id'];

    public function designs_materials(): BelongsToMany
    {
        return $this->belongsToMany(
            Materials::class,            // Related model
            'designs_materials',         // Pivot table name
            'design_id',                 // This model's FK in pivot table
            'material_id'                // Related model's FK in pivot table
        )
            ->withPivot('quantity_used')
            ->withTimestamps();
    }

    public function design_categories(): BelongsTo
    {
        return $this->belongsTo(DesignCategory::class, 'category_id');
    }
}
