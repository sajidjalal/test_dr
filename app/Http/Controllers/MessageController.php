<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\Chat;
use Illuminate\Support\Facades\Storage;

class MessageController extends Controller
{
    // List messages of a chat
    public function index(Chat $chat)
    {
        $chat->load(['messages.user','messages.replyTo']);
        return response()->json($chat->messages);
    }

    // Send a message
    public function store(Request $request, Chat $chat)
    {
        $request->validate([
            'text' => 'nullable|string',
            'attachment' => 'nullable|file|max:10240', // max 10MB
            'reply_to' => 'nullable|exists:messages,id'
        ]);

        $data = [
            'chat_id' => $chat->id,
            'user_id' => $request->user()->id,
            'text' => $request->text,
            'reply_to' => $request->reply_to ?? null,
            'status' => 'sent'
        ];

        // Handle file upload
        if($request->hasFile('attachment')){
            $file = $request->file('attachment');
            $path = $file->store('chat_files','public');
            $data['attachment'] = $path;
        }

        $message = Message::create($data);

        // Broadcast event for real-time
        broadcast(new \App\Events\GotMessage($message))->toOthers();

        return response()->json($message);
    }
}
