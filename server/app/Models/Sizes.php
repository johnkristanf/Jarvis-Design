<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Sizes extends Model
{
    protected $fillable = [
        'name'
    ];

    public function preferred_design(): HasOne 
    {
        return $this->hasOne(PreferredDesign::class);
    }
}
