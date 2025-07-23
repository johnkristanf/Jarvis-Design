<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Materials extends Model
{
    protected $guarded = ['id'];

    public function designs()
    {
        return $this->morphedByMany(
            Designs::class,
            'designable',
            'designables_materials',
        )
            ->withPivot('quantity_used')
            ->withTimestamps();
    }



    public function uploadedDesigns()
    {
        return $this->morphedByMany(UploadedDesign::class, 'designable', 'designables_materials')
            ->withPivot('quantity_used')
            ->withTimestamps();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(MaterialsCategory::class, 'category_id');
    }

    public function subtractQuantity($amount)
    {
        $this->quantity -= $amount;
        $this->save();
    }


    public function products()
    {
        return $this->hasMany(Products::class, 'fabric_type_id');
    }
}
