<?php

namespace App\Http\Controllers;

use App\Jobs\SendMessage;
use App\Models\Chat;
use App\Models\ChatParticipant;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $user = User::where('id', auth()->id())->select([
            'id',
            'name',
            'email',
        ])->first();

        return view('home', [
            'user' => $user,
            'base_url' => config('app.url'),
        ]);
    }

    public function getUsers(Request $request)
    {
        $data = [];
        $currentUserId = $request->user()->id;
        $data['users'] = User::where('id', '!=', $currentUserId)
            ->get(['id', 'name', 'email']);

        $currentUserChatId = Chat::where('is_group', false)
        ->whereHas('participants', fn($q) => $q->where('user_id', $currentUserId))
        ->pluck('id');

        $alreadyChatParticipant = ChatParticipant::whereHas('chat', fn($q) => $q->where('is_group', false))
        ->whereIn('chat_id', $currentUserChatId)
        ->pluck('user_id');

        $data['single_chat_users'] = User::whereNotIn('id', $alreadyChatParticipant)->where('id', '!=', $currentUserId)->get();

        return response()->json($data);
    }

    public function test()
    {
        $user = User::where('id', auth()->id())->select([
            'id',
            'name',
            'email',
        ])->first();

        return view('test', [
            'user' => $user,
            'base_url' => config('app.url'),
        ]);
    }
}
