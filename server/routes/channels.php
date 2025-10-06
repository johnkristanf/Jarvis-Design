<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

Broadcast::channel('payments.update', function ($user) {
    Log::info("Socket Authenticated User: ", [$user]);
    return true;
});
