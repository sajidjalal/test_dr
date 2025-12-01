<?php

namespace App\Events;

use App\Models\Message;
use App\Models\MessageReceiver;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MessageNotification implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('user.'.$this->message['receiver_user_id'] .'.chat.'.$this->message['chat_id']);
    }

    public function broadcastWith()
    {

        return [
            'message' => $this->message,
            'sender_name' => $this->message->sender_name,
        ];
    }
}
