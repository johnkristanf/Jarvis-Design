<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PreferredDesign extends Model
{
    protected $guarded = [ 'id' ];

    public function size(): BelongsTo 
    {
        return $this->belongsTo(Sizes::class, 'size_id');
    }

    public function color(): BelongsTo 
    {
        return $this->belongsTo(Colors::class, 'color_id');
    }
}
