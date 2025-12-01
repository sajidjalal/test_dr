<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;

//Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
//    return (int) $user->id === (int) $id;
//});

Broadcast::channel('channel_for_everyone', function ($user) {
    return true;
});
Broadcast::channel('user.{userId}.chat.{chatId}', function ($user, $userId, $chatId) {
    return (int)$user->id === (int)$userId;
});
Broadcast::channel('chat.{chatId}', function ($user, $userId, $chatId) {
    return $user ? true : false;
});
