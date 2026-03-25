<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductStyle extends Model
{
    protected $guarded = ['id'];

    public function designCategory()
    {
        return $this->belongsTo(DesignCategory::class);
    }
}
