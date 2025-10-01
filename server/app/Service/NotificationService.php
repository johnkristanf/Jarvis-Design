<?php

namespace App\Service;

use App\Events\NotifyOrderStatus;
use App\Models\AdminNotification;
use App\Events\NotifyAdminEvent;
use App\Models\Notifications;
use Illuminate\Support\Facades\Log;
use Exception;

class NotificationService
{
    public function notifyUserOrder($orders, $userID, $status)
    {
        try {
            $notification = Notifications::create([
                'order_id' => $orders->id,
                'user_id' => $userID,
                'status' => $status,
            ]);

            broadcast(new NotifyOrderStatus($orders, $notification));

        } catch (Exception $e) {
            Log::error('Broadcast exception: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
        }
    }


    public function notifyAdmin($type, $message)
    {
        try {
            $notification = AdminNotification::create([
                'type' => $type,
                'message' => $message,
            ]);

            broadcast(new NotifyAdminEvent($notification));

        } catch (Exception $e) {
            Log::error('Broadcast exception: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
        }
    }

}