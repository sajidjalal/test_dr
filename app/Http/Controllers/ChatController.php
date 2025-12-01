<?php

namespace App\Http\Controllers;

use App\Events\ReadMessage;
use App\Jobs\SendMessage;
use Illuminate\Http\Request;
use App\Models\Chat;
use App\Models\ChatParticipant;
use App\Models\Message;
use App\Models\MessageReceiver;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ChatController extends Controller
{
    // List all chats of current user
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $chats = Chat::whereHas('participants', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })
            ->with(['participants.user', 'lastMessage.sender'])
            ->get();

        $chats = $chats->map(function ($chat) use ($userId) {
            $unReadCount = MessageReceiver::whereHas('message', function ($q) use ($chat) {
                $q->where('chat_id', $chat->id);
            })
                ->where('user_id', auth()->id())
                ->whereNot('status', MessageReceiver::READ)
                ->count();
            return [
                'id' => $chat->id,
                'name' => $chat->is_group ? $chat->name : $chat->participants->where('user_id', '!=', auth()->id())->first()->user->name,
                'is_group' => $chat->is_group,
                'last_message' => $chat->lastMessage->text ?? '',
                'unReadCount' => $unReadCount != 0 ? $unReadCount : '',
            ];
        });

        return response()->json($chats);
    }

    // Create new chat (1-to-1 or group)
    public function store(Request $request)
    {
        $request->validate([
            'is_group' => 'required|boolean',
            'participants' => 'array',
            'participants.*' => 'exists:users,id',
            'name' => 'required_if:is_group,true|string|max:255',
            'other_user_id' => 'required_if:is_group,false|exists:users,id'
        ]);

        $userId = $request->user()->id;

        DB::beginTransaction();
        try {
            if ($request->is_group) {
                $chat = Chat::create([
                    'name' => $request->name,
                    'is_group' => true
                ]);

                // Add creator as admin
                ChatParticipant::create([
                    'chat_id' => $chat->id,
                    'user_id' => $userId,
                    'is_admin' => true
                ]);

                // Add selected participants
                foreach ($request->participants as $p) {
                    ChatParticipant::create([
                        'chat_id' => $chat->id,
                        'user_id' => $p
                    ]);
                }
            } else {
                // 1-to-1 chat
                $otherId = $request->other_user_id;

                // check if chat already exists
                $chat = Chat::where('is_group', false)
                    ->whereHas('participants', fn($q) => $q->where('user_id', $userId))
                    ->whereHas('participants', fn($q) => $q->where('user_id', $otherId))
                    ->first();

                if (!$chat) {
                    $chat = Chat::create(['is_group' => false]);
                    ChatParticipant::create(['chat_id' => $chat->id, 'user_id' => $userId]);
                    ChatParticipant::create(['chat_id' => $chat->id, 'user_id' => $otherId]);
                }
            }

            DB::commit();
            return response()->json($chat);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Get messages for a chat
    public function messages(Chat $chat, Request $request)
    {
        $data = [];
        // Ensure user is participant
        if (!$chat->participants()->where('user_id', $request->user()->id)->exists()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $messages = $chat->messages()->withTrashed()->with('sender:id,name')->get();

        $data['messages'] = $messages->map(function ($message) {
            $is_message_seen = MessageReceiver::where('message_id', $message->id)
            ->where('status', MessageReceiver::READ)
            ->exists();

            return [
                'id' => $message->id,
                'user_id' => $message->user_id,
                'text' => $message->text,
                'attachment' => $message->attachment,
                'sender_name' => $message->sender->name,
                'deleted_at' => $message->deleted_at ?? '',
                'created_at' => $message->created_at,
                'is_message_seen' => $is_message_seen,
            ];
        });

        $data['unReadCount'] = MessageReceiver::whereHas('message', function ($q) use ($chat) {
                $q->where('chat_id', $chat->id);
            })
            ->where('user_id', auth()->id())
            ->whereNot('status', MessageReceiver::READ)
            ->count();
        $data['unReadCount'] != 0 ? $data['unReadCount'] : '';
        return response()->json($data);
    }

    // Send message
    public function sendMessage(Chat $chat, Request $request)
    {
        $request->validate([
            'text' => 'nullable|string',
            'attachment' => 'nullable|file'
        ]);

        if (!$chat->participants()->where('user_id', $request->user()->id)->exists()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $fields = [
            'chat_id' => $chat->id,
            'user_id' => $request->user()->id,
            'text' => $request->text ?? null,
        ];

        if ($request->file('attachment')) {
            $file = $request->file('attachment');
            $disk = env('MEDIA_DISK', 'public');
            $folder = Message::MESSAGE_ATTACHMENT_PATH;
            $timestamp = time();
            $fileName = $file->getClientOriginalName();
            $fileName = "{$timestamp}-{$fileName}";
            Storage::disk($disk)->putFileAs($folder, $file, $fileName);
            $fields['attachment'] = $fileName;
        }

        $message = Message::create($fields);

        if($message){
            $participants = $chat->participants()->whereNot('user_id', $request->user()->id)->pluck('user_id');
            foreach($participants as $participant){
                $receiver = MessageReceiver::create([
                    'message_id' => $message->id,
                    'user_id' => $participant,
                ]);
                // TODO: Broadcast event for real-time update via WebSockets
                SendMessage::dispatch($message, $receiver);
            }
        }

        return response()->json($message);
    }

    public function readMessage(Chat $chat, Request $request)
    {
        if (!$chat->participants()->where('user_id', auth()->id())->exists()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $message_id = $chat->messages()->pluck('id');

        $message_read = MessageReceiver::where('user_id', auth()->id())
        ->whereIn('message_id', $message_id)
        ->update(['status' => MessageReceiver::READ]);

        // TODO: Broadcast event for real-time update via Pusher/WebSockets
        ReadMessage::dispatch($chat);

        $data['unReadCount'] = MessageReceiver::whereHas('message', function ($q) use ($chat) {
            $q->where('chat_id', $chat->id);
        })
            ->where('user_id', auth()->id())
            ->whereNot('status', MessageReceiver::READ)
            ->count();
        $data['unReadCount'] != 0 ? $data['unReadCount'] : '';
        return response()->json($data);
    }

    public function deleteMessage(Chat $chat, Message $message, Request $request)
    {
        if (!$chat->participants()->where('user_id', auth()->id())->exists() && !($message->user_id == auth()->id())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $message->delete();

        $receiver = $message->receivers()->get();

        if (count($receiver) > 0) {
            foreach ($receiver as $id) {
                // TODO: Broadcast event for real-time update via Pusher/WebSockets
                SendMessage::dispatch($message, $id);
            }
        }

        $data['messages'] = $chat->messages()->withTrashed()->with('sender')->get();

        return response()->json($data);
    }

    public function addChatParticipant(Chat $chat, Request $request){
        $request->validate([
            'participant_user_id' => 'array',
            'participant_user_id.*' => 'exists:users,id',
        ]);
        if (!$chat->participants()->where('user_id', auth()->id())->where('is_admin', 1)->exists()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // if ($request->participant_user_id) {
        //     if($chat->participants()->where('user_id', $request->participant_user_id)->exists()){
        //         return response()->json(['error' => 'Participant Already Exist in the Group Chat'], 400);
        //     }
        // }

        if($request->participant_user_id){
            foreach ($request->participant_user_id as $value) {
                $fields = [
                    'chat_id' => $chat->id,
                    "user_id" => $value
                ];
        
                ChatParticipant::updateOrCreate($fields,[]);
            }
        }
        return response()->json(['message' => 'User Added']);
    }

    public function removeChatParticipant(Chat $chat, Request $request){
        $request->validate([
            'participant_user_id' => 'array',
            'participant_user_id.*' => 'exists:users,id',
        ]);
        if (!$chat->participants()->where('user_id', auth()->id())->where('is_admin', 1)->exists()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $field = ["deleted_at" => now()];
        $chat->participants()->whereIn('user_id', $request->participant_user_id)->update($field);
    }

    public function getChatParticipant(Chat $chat, Request $request)
    {
        if (!$chat->participants()->where('user_id', auth()->id())->where('is_admin', 1)->exists()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $participants_id = $chat->participants()->whereNot('user_id', auth()->id())->pluck('user_id');
        $data = User::whereIn('id', $participants_id)->get();

        return response()->json($data);
    }

    public function getOtherParticipant(Chat $chat, Request $request)
    {
        if (!$chat->participants()->where('user_id', auth()->id())->where('is_admin', 1)->exists()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $participants_id = $chat->participants()->pluck('user_id');
        $data = User::whereNotIn('id', $participants_id)->get();

        return response()->json($data);
    }
}
