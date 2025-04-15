<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Materials extends Model
{
    protected $guarded = ['id'];

    public function designs(): BelongsToMany
    {
        return $this->belongsToMany(Designs::class, 'designs_materials');
    }
}
