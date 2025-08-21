<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MaterialsCategory extends Model
{
    protected $guarded = ['id'];

    protected $table = 'materials_category';

    public function material(): HasOne
    {
        return $this->hasOne(Materials::class, 'category_id');
    }
}
