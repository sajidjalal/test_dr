<?php

namespace App\Jobs;

use App\Events\GotMessage;
use App\Events\MessageNotification;
use App\Models\ChatParticipant;
use App\Models\Message;
use App\Models\MessageReceiver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public Message $message, public MessageReceiver $message_receiver)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        GotMessage::dispatch([
            'id' => $this->message->id,
            'user_id' => $this->message->user_id,
            'receiver_id' => $this->message_receiver->id,
            'receiver_user_id' => $this->message_receiver->user_id,
            'chat_id' => $this->message->chat_id,
            'text' => $this->message->text,
            'time' => $this->message->time,
        ]);
        // MessageNotification::dispatch([
        //     'id' => $this->message->id,
        //     'user_id' => $this->message->user_id,
        //     'receiver_id' => $this->message_receiver->id,
        //     'receiver_user_id' => $this->message_receiver->user_id,
        //     'chat_id' => $this->message->chat_id,
        //     'text' => $this->message->text,
        //     'time' => $this->message->time,
        // ]);
    }
}
