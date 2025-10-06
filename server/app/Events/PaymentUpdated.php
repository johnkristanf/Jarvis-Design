<?php

namespace App\Events;

use App\Models\OrderPayment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PaymentUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public OrderPayment $orderPayment;
    /**
     * Create a new event instance.
     */
    public function __construct(OrderPayment $orderPayment)
    {
        $this->orderPayment = $orderPayment;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('payments.update'),
        ];
    }

    public function broadcastAs()
    {
        return 'payment.update';
    }

    public function broadcastWith()
    {
        return [
            'payment' => [
                'id' => $this->orderPayment->id
            ]
        ];
    }
}
