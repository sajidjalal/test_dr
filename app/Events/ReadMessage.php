<?php

namespace App\Events;

use App\Models\Chat;
use App\Models\Message;
use App\Models\MessageReceiver;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class ReadMessage implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;


    public function __construct(public Chat $chat)
    {
    }

    public function broadcastOn()
    {
        return new Channel('chat.'.$this->chat->id);
    }

    public function broadcastWith()
    {
        return ['chat' => $this->chat];
    }
}
