<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notifications extends Model
{
    protected $table = 'user_order_notifications';
    protected $guarded = ['id'];


    public function orders()
    {
        return $this->belongsTo(Orders::class, 'order_id');
    }

    public function users()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
