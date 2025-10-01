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
                'user:id,name,email',
                'sizes',
                'product:id,name',
                'order_payments' => function ($q) {
                    $q->select([
                        'id',
                        'payment_number',
                        'payment_method_id',
                        'order_id',
                        'amount_applied',
                        'status',
                    ])->with(['payment_attachments:id,order_payment_id,url', 'payment_methods:id,name']); // ✅ include payment method details
                },
            ])
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
                    'product_id',
                    'created_at',
                ]);

            $authenticatedUser = Auth::user();
            if (! $authenticatedUser->isAdmin()) {
                $query->where('user_id', '=', $authenticatedUser->id);
            }

            if ($search) {
                $query->where('order_number', 'ILIKE', "%{$search}%")
                    ->orWhere('status', 'ILIKE', "%{$search}%");
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

    public function createAndLoadOrderPayment($paymentMethodID, $orderID, $userID, $attachmentURL)
    {
        $orderPayment = OrderPayment::create([
            'payment_number'    => $this->generatePaymentNumber(),
            'payment_method_id' => $paymentMethodID,
            'order_id'          => $orderID,
            'user_id'           => $userID,
            'amount_applied'    => 0, // admin updates later
            'status'            => OrderPayment::IN_REVIEW,
        ]);

        PaymentAttachment::create([
            'order_payment_id' => $orderPayment->id,
            'url'      => $attachmentURL,
        ]);

        return $orderPayment;
    }
}
