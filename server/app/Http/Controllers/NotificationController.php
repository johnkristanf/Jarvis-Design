<?php

namespace App\Http\Controllers;

use App\Models\AdminNotification;
use App\Models\Notifications;
use App\Service\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct() {
        $this->notificationService = new NotificationService;
    }
    public function adminNotifications()
    {
        $notifications = AdminNotification::select('id', 'type', 'message', 'is_read', 'created_at')
            ->latest()
            ->get();
        return response()->json($notifications);
 
    }

    public function orderNotificationsPerUser()
    {
        $orderNotifications = Notifications::with(['orders.product', 'users']) // eager load related order
            ->select('id', 'order_id', 'user_id', 'created_at', 'status', 'is_read')
            ->where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->get();

        return response()->json($orderNotifications, 200);
    }


    public function notificationAsRead(Request $request)
    {
        $validated = $request->validate([
            'notification_id' => 'required|numeric',
            'is_admin' => 'required|boolean',
        ]);

        Log::info("validated: ", [$validated]);

        $notificationModel = $validated['is_admin'] 
            ? AdminNotification::class 
            : Notifications::class;

        // Update the notification
        $updatedNotificationID = $notificationModel::where('id', $validated['notification_id'])
            ->update(['is_read' => true]);

        return response()->json([
            'msg' => 'Notification Read Update Successfully',
            'notification_id' => $updatedNotificationID,
        ], 200);
    }


    public function markAllNotifcationAsRead(Request $request)
    {
        $validated = $request->validate([
            'is_admin' => 'required|boolean',
        ]);

        $notificationModel = $validated['is_admin'] 
            ? AdminNotification::class 
            : Notifications::class;

        $query = $notificationModel::where('is_read', false);

        // Only add user_id condition if it's not admin
        if (!$validated['is_admin']) {
            $query->where('user_id', Auth::id());
        }

        $query->update(['is_read' => true]);

        return response()->json([
            'msg' => 'Notification Read All Successfully',
        ], 200);
    }

    public function notifyStockAlert(Request $request)
    {
        $validated = $request->validate([
            'low_stock_data' => 'required'
        ]);

        $lowStockData = $validated['low_stock_data'];
        $itemCount = count($lowStockData);

        // Build the message with all low stock items
        $itemsList = collect($lowStockData)->map(function($item) {
            return sprintf(
                "• %s\n  Category: %s\n  Current: %d %s | Reorder at: %d %s\n  Status: %s",
                $item['name'],
                $item['category']['name'] ?? 'N/A',
                $item['quantity'],
                $item['unit'],
                $item['reorder_level'],
                $item['unit'],
                $item['quantity'] <= 0 ? '🔴 OUT OF STOCK' : '⚠️ LOW STOCK'
            );
        })->implode("\n\n");

        $message = sprintf(
            "⚠️ Stock Alert - %d Item%s Need Attention!\n\n%s\n\n📋 Please review and reorder as needed.",
            $itemCount,
            $itemCount > 1 ? 's' : '',
            $itemsList
        );

        $this->notificationService->notifyAdmin(
            AdminNotification::STOCK_NOTIFICATION_TYPE,
            $message
        );

        return response()->json([
            'msg' => 'Added Stock Alert Notification Successfully',
        ], 200);
    }

}
