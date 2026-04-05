<?php

namespace App\Service;

use App\Jobs\ProcessPayment;
use App\Jobs\SendOrderConfirmation;
use App\Models\Notifications;
use App\Models\OrderPayment;
use App\Models\Orders;
use App\Models\PaymentAttachment;
use App\Traits\HandleAttachments;
use App\Traits\OrderTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    use HandleAttachments, OrderTrait;

    protected $client;

    public function __construct()
    {
        $this->client = new \GuzzleHttp\Client;
    }

    public function allOrders($limit, $search)
    {

            $query = Orders::with([
                'user:id,name,email,phone_number,address',
                'items.sizes',
                'items.product:id,name',
                'items.customizations',
                'discount',
                'order_payments' => function ($q) {
                    $q->select([
                        'id',
                        'payment_number',
                        'payment_method_id',
                        'order_id',
                        'amount_applied',
                        'status',
                        'remarks',
                    ])
                    ->with([
                        'payment_attachments:id,order_payment_id,url',
                        'payment_methods:id,name',
                    ])
                    ->orderBy('created_at', 'desc'); // ✅ payments sorted ascending
                },
            ])
                ->leftJoin('order_payments', 'order_payments.order_id', '=', 'orders.id')
                ->select([
                    'orders.*',
                    DB::raw('COALESCE(SUM(order_payments.amount_applied), 0) AS total_paid'),
                    DB::raw('(orders.total_price - COALESCE(SUM(order_payments.amount_applied), 0)) AS balance'),
                ])
                ->groupBy('orders.id');

            $authenticatedUser = Auth::user();
            if (! $authenticatedUser->isAdmin()) {
                $query->where('orders.user_id', '=', $authenticatedUser->id);
            }

            if ($search) {
                $query->where('orders.order_number', 'ILIKE', "%{$search}%")
                    ->orWhere('orders.status', 'ILIKE', "%{$search}%");
            }

            $orders = $query->latest()->paginate($limit);
            // $orders = clone $orders; -> this doesn't do deep cloning
            // If transformOrderDesignToS3Temp expects $order->product, it will need updating
            // Let's modify transformOrderDesignToS3Temp to handle items
            return $this->transformOrderDesignToS3Temp($orders);

    }


    public function updateStatus($orderID, $status)
    {

        $order = Orders::findOrFail($orderID);
        $order->status = $status;
        $order->save();

        $notificationService = new \App\Service\NotificationService();
        $notificationService->notifyUserOrder($order, $order->user_id, $status);

        return $order->id;
    }


    public function processPayment($orderID, $paymentAttachmentFile)
    {
        $paymentAttachmentURL = $this->uploadToS3(
            root: 'payment',
            sub: Auth::id(),
            file: $paymentAttachmentFile
        );

        ProcessPayment::dispatch(Auth::user()->id, $orderID, $paymentAttachmentURL)->afterCommit();
    }

    public function sendOrderConfirmationEmail(Orders $orders)
    {
        $orders->load(['user']);
        SendOrderConfirmation::dispatch($orders)->afterCommit();
    }

    public function createAndLoadOrderPayment($paymentMethodID, $orderID, $userID, $attachmentURL, $amount = 0, $status = OrderPayment::IN_REVIEW)
    {
        $orderPayment = OrderPayment::create([
            'payment_number'    => $this->generatePaymentNumber(),
            'payment_method_id' => $paymentMethodID,
            'order_id'          => $orderID,
            'user_id'           => $userID,
            'amount_applied'    => $amount,
            'status'            => $status,
        ]);

        if ($attachmentURL) {
            PaymentAttachment::create([
                'order_payment_id' => $orderPayment->id,
                'url'      => $attachmentURL,
            ]);
        }

        return $orderPayment;
    }
}
