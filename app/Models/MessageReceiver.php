<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MessageReceiver extends Model
{
    use SoftDeletes;
    const SENT = 0;
    const DELIVERED = 1;
    const READ = 2;
    protected $table = 'message_receivers';
    protected $guarded = [];

    public function message()
    {
        return $this->belongsTo(Message::class, 'message_id', 'id');
    }
    public function receiver()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
