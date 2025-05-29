<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DesignCategory extends Model
{
    protected $guarded = ['id'];
    protected $table = 'design_categories';

    public function designs(): HasMany
    {
        return $this->hasMany(Designs::class, 'category_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Products::class, 'category_id');
    }


    public function sublimation_attribute(): HasOne
    {
        return $this->hasOne(Products::class, 'category_id');
    }
}
