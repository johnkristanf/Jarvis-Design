<?php

namespace App\Events;

use App\Models\Notifications;
use App\Models\Orders;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotifyOrderStatus
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Orders $orders;
    public Notifications $notifcation;

    /**
     * Create a new event instance.
     */
    public function __construct(Orders $orders, Notifications $notifcation)
    {
        $this->orders = $orders;
        $this->notifcation = $notifcation;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('order.notification'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'notify.order.status';
    }


    public function broadcastWith(): array
    {
        return [
            'notification' => [
                'id'         => $this->notifcation->id,
                'order_id'   => $this->orders->id,
                'status'     => $this->orders->status,
                'is_read'    => $this->notifcation->is_read ?? false,
                'created_at' => $this->notifcation->created_at?->toISOString(),
            ],
        ];
    }
}
