<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::call(function () {
    User::query()->update(['prompt_limit' => 10]);
})
    ->dailyAt('00:00') 
    ->timezone('Asia/Manila');