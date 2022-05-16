<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('notification', function ($user) {
    return true;
});

Broadcast::channel('chat1', function ($user) {
    return true;
});

Broadcast::channel('room', function ($user) { // for presence channel return data about user -> https://laravel.com/docs/9.x/broadcasting#authorizing-presence-channels
    return ['id' => $user->id, 'name' => $user->name];
});
