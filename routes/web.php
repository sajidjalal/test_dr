<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\UserController;
use App\Models\User;

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::group(['middleware' => 'throttle:6,1'], function () {
    //allowed 6 attempts every 1 minute
    Route::post('generate-otp', [AuthController::class, 'generateOtp'])->name('generate.otp');
    Route::post('verify-otp', [AuthController::class, 'verifyOtp'])->name('verify.otp');
});

Route::post('user-register', [UserController::class, 'userRegister'])->name('user.register');

Route::get('/users', [HomeController::class, 'getUsers'])->name('getUsers');
Route::get('/home', [HomeController::class, 'index'])->name('home');
Route::get('/test', [HomeController::class, 'test'])->name('test');
Route::get('/messages', [HomeController::class, 'messages'])->name('messages');
Route::post('/message', [HomeController::class, 'message'])->name('message');


Route::get('/chats', [ChatController::class, 'index']);
Route::post('/chats', [ChatController::class, 'store']);
Route::get('/chats/{chat}/messages', [ChatController::class, 'messages']);
Route::post('/chats/{chat}/messages', [ChatController::class, 'sendMessage']);
Route::post('/chats/{chat}/read-messages', [ChatController::class, 'readMessage'])->name('read.message');
Route::post('/chats/{chat}/delete-message/{message}', [ChatController::class, 'deleteMessage'])->name('delete.message');
Route::post('/chats/{chat}/add-chat-participant', [ChatController::class, 'addChatParticipant'])->name('add.chat.participant');
Route::post('/chats/{chat}/remove-chat-participant', [ChatController::class, 'removeChatParticipant'])->name('remove.chat.participant');
Route::get('/chats/{chat}/get-chat-participant', [ChatController::class, 'getChatParticipant'])->name('get.chat.participant');
Route::get('/chats/{chat}/get-other-chat-participant', [ChatController::class, 'getOtherParticipant'])->name('get.other.chat.participant');


Route::delete('log-viewer/api/files/{fileIdentifier}', function () {
    abort(403, 'Deleting log files is disabled.');
});

Route::delete('log-viewer/api/folders/{folderIdentifier}', function () {
    abort(403, 'Deleting log folders is disabled.');
});

Route::post('log-viewer/api/delete-multiple-files', function () {
    abort(403, 'Bulk log file deletion is disabled.');
});
