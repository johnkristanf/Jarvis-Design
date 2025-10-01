<?php

namespace App\Events;

use App\Models\AdminNotification;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NotifyAdminEvent implements ShouldBroadcast, ShouldQueue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public AdminNotification $notification)
    {
        //
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('admin.notification'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'notify.admin';
    }


    public function broadcastWith(): array
    {
        Log::info("notification", [$this->notification]);
        Log::info("EVENT HERE");
        return [
            'notification' => [
                'id'         => $this->notification->id,
                'type'   => $this->notification->type,
                'message'     => $this->notification->message,
                'is_read'    => $this->notification->is_read ?? false,
                'created_at' => $this->notification->created_at?->toISOString(),
            ],
        ];
    }
}
