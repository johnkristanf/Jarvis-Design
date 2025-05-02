<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderLogs extends Model
{
    protected $guarded = ['id'];

    public function users(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }


    public function orders(): BelongsTo
    {
        return $this->belongsTo(Orders::class, 'order_id');
    }
}
