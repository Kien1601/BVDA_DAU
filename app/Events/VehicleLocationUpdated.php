<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class VehicleLocationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    /**
     * Truyền mảng thay vì Model: gửi đúng các trường bản đồ cần, không lộ thêm gì.
     */
    public function __construct(public array $position)
    {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('admin.monitor')];
    }

    public function broadcastAs(): string
    {
        return 'vehicle.location.updated';
    }

    public function broadcastWith(): array
    {
        return $this->position;
    }
}