<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Roles extends Model
{
    const ADMIN_ROLE_ID = 1;
    const CUSTOMER_ROLE_ID = 2;
    
    protected $fillable = [
        'name',
    ];

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'role_id');
    }
}
