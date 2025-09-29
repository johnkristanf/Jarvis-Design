<?php

namespace App\Service;

use App\Events\NotifyOrderStatus;
use App\Jobs\ProcessPayment;
use App\Jobs\SendOrderConfirmation;
use App\Models\Notifications;
use App\Models\OrderPayment;
use App\Models\Orders;
use App\Models\OrderStatus;
use App\Models\PaymentAttachment;
use App\Models\PaymentMethod;
use App\Traits\HandleAttachments;
use App\Traits\OrderTrait;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PaymentService
{
    use HandleAttachments, OrderTrait;

    protected $client;

    public function __construct()
    {
        $this->client = new \GuzzleHttp\Client;
    }

    public function allOrders($limit)
    {

            $query = Orders::with(['user:id,name,email', 'sizes'])
                ->select([
                    'id',
                    'order_number',
                    'product_unit_price',
                    'color',
                    'phone_number',
                    'address',
                    'design_type',
                    'order_option',
                    'total_quantity',
                    'total_price',
                    'solo_quantity',
                    'own_design_url',
                    'business_design_url',
                    'status',
                    'delivery_date',
                    'user_id',
                    'created_at',
                ]);

            $authenticatedUser = Auth::user();
            if (! $authenticatedUser->isAdmin()) {
                $query->where('user_id', '=', $authenticatedUser->id);
            }

            $orders = $query->latest()->paginate($limit);
            return $this->transformOrderDesignToS3Temp($orders);

    }


    public function updateStatus($orderID, $status)
    {

        $order = Orders::findOrFail($orderID);
        $order->status = $status;
        $order->save();

        Notifications::create([
            'order_id' => $order->id,
            'status' => $status,
            'user_id' => $order->user_id,
        ]);

        return $order->id;
    }

    public function getNotificationPerUser($userID)
    {
        $notifications = Notifications::with(['orders.product', 'users']) // eager load related order
            ->select('id', 'order_id', 'user_id', 'created_at', 'status', 'is_read')
            ->where('user_id', $userID)
            ->orderByDesc('created_at')
            ->get();

        return $notifications;
    }

    public function updateNotification($notificationID)
    {
        Notifications::where('id', $notificationID)->update([
            'is_read' => true,
        ]);

        return $notificationID;
    }

    public function updateAllNotificationsAsRead()
    {
        Notifications::where('is_read', false)->update([
            'is_read' => true,
        ]);

        return 'success';
    }


    public function processPayment($orderID, $paymentAttachmentFile)
    {
        $paymentAttachmentURL = $this->uploadToS3(
            root: 'payment',
            sub: Auth::id(),
            file: $paymentAttachmentFile
        );

        ProcessPayment::dispatch($orderID, $paymentAttachmentURL)->afterCommit();
    }


    public function sendOrderConfirmationEmail(Orders $orders)
    {
        $orders->load(['user']);
        SendOrderConfirmation::dispatch($orders)->afterCommit();
    }


    public function notifyUser($orders, $userID, $status)
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
}
