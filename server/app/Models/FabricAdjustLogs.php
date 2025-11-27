<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FabricAdjustLogs extends Model
{
    const ADDED = 'added';
    const REDUCED = 'reduced';
    
    protected $guarded = ['id'];

    public function material()
    {
        return $this->belongsTo(Materials::class, 'material_id');
    }
}
