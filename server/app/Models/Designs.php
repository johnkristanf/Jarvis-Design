<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Designs extends Model
{
    protected $guarded = [ 'id' ];

    public function materials(): BelongsToMany 
    {
        return $this->belongsToMany(Materials::class, 'designs_materials');
    }
}
