<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'chat_id',
        'user_id',
        'text',
        'reply_to',
        'attachment',
        'status'
    ];

    const MESSAGE_ATTACHMENT_PATH = 'public/uploads/messages';

    public function chat()
    {
        return $this->belongsTo(Chat::class);
    }

    public function chat_participants()
    {
        return $this->belongsTo(ChatParticipant::class, 'chat_id', 'chat_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function receivers()
    {
        return $this->hasMany(MessageReceiver::class, 'message_id', 'id');
    }
}
