<?php

namespace App\Jobs;

use App\Mail\OrderConfirmationEmail;
use App\Models\Orders;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendOrderConfirmation implements ShouldQueue
{
    use Dispatchable, Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public Orders $orders)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Orders: ", [$this->orders]);
        Mail::to($this->orders->user->email)->send(new OrderConfirmationEmail(orders: $this->orders));

    }
}
