<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SublimationAttributes extends Model
{
    protected $guarded = ['id'];

    public function design_categories(): BelongsTo
    {
        return $this->belongsTo(DesignCategory::class, 'category_id');
    }
}
