<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Materials extends Model
{
    protected $guarded = ['id'];

    public function designs()
    {
        return $this->belongsToMany(
            Designs::class,            // Related model
            'designs_materials',         // Pivot table name
            'design_id',                 // This model's FK in pivot table
            'material_id'                // Related model's FK in pivot table
        )
            ->withPivot('quantity_used')
            ->withTimestamps();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(MaterialsCategory::class, 'category_id');
    }

    public function subtractQuantity($amount)
    {
        $this->quantity -= $amount;
        $this->save();
    }
}
