<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Designs extends Model
{
    protected $guarded = ['id'];

    public function materials()
    {
        return $this->morphToMany(
            Materials::class,           // related model
            'designable',               // morph name (used in designable_type and designable_id)
            'designables_materials',   // pivot table
            'designable_id',           // foreign key on pivot pointing to this model
            'material_id'              // foreign key on pivot pointing to Materials
        )
            ->withPivot('quantity_used')
            ->withTimestamps();
    }

    public function design_categories(): BelongsTo
    {
        return $this->belongsTo(DesignCategory::class, 'category_id');
    }
}
