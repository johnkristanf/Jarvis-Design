<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DesignCategory extends Model
{
    protected $guarded = ['id'];
    
    public function designs(): HasMany
    {
       return $this->hasMany(Designs::class, 'category_id');
    }
}
