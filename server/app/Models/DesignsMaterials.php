<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DesignsMaterials extends Model
{
    protected $guarded = ['id'];

    public function designs(): BelongsTo
    {
        return $this->belongsTo(Designs::class, 'design_id');
    }

    public function materials(): BelongsTo
    {
        return $this->belongsTo(Materials::class, 'material_id');
    }
}
