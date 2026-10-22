<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Message $message) {}

    // Gửi cho khách của hội thoại + kênh chung của nhân viên
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.' . $this->message->conversation_id),
            new PrivateChannel('chat.staff'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

        public function broadcastWith(): array
    {
        return $this->message->toChatArray();
    }
}