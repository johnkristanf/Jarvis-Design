<?php

namespace App\Jobs;

use App\Mail\OrderConfirmationEmail;
use App\Models\Orders;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendOrderConfirmation implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    // public $messageGroupId;

    /**
     * Create a new job instance.
     */
    public function __construct(public Orders $orders)
    {
        // $this->messageGroupId = 'order-emails-' . $this->orders->order_number;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Mail::to($this->orders->user->email)->send(new OrderConfirmationEmail(orders: $this->orders));
    }
}
