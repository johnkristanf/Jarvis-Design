<?php

namespace App\Jobs;

use App\Models\OrderPayment;
use App\Models\PaymentAttachment;
use App\Models\PaymentMethod;
use App\Traits\OrderTrait;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ProcessPayment implements ShouldQueue
{
    use Queueable, OrderTrait;

    protected $userID;
    protected $orderID;
    protected $paymentAttachmentURL;

    /**
     * Create a new job instance.
     */
    public function __construct($userID, $orderID, $paymentAttachmentURL)
    {
        $this->userID = $userID;
        $this->orderID = $orderID;
        $this->paymentAttachmentURL = $paymentAttachmentURL;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        // Default payment method: GCash
        $paymentMethodID = PaymentMethod::where('code', PaymentMethod::GCASH)->value('id') ?? 0;

        // Create order payment
        $orderPayment = OrderPayment::create([
            'payment_number'    => $this->generatePaymentNumber(),
            'payment_method_id' => $paymentMethodID,
            'order_id'          => $this->orderID,
            'user_id'           => $this->userID,
            'amount_applied'    => 0, // admin updates later
            'status'            => OrderPayment::IN_REVIEW,
        ]);

        // Attach proof of payment
        PaymentAttachment::create([
            'url'             => $this->paymentAttachmentURL,
            'order_payment_id'=> $orderPayment->id,
        ]);
    }
}
