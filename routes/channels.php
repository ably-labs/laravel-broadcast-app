<?php

use App\Models\User;
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

/*
 * Register a private channel, this is not necessary for public channels
 * The return value determines whether the user is authorized to join this private channel

Broadcast::channel('notification', function ($user) {
    return true;
});
*/

Broadcast::channel('room-{roomId}', function ( User $user, $roomId) { // for presence channel return data about user -> https://laravel.com/docs/9.x/broadcasting#authorizing-presence-channels
    if($user->canJoinRoom($roomId))
        return ['id' => $user->id, 'name' => $user->name, 'capability' => ["subscribe", "history", "channel-metadata", "presence", "publish"]];

    return false;
});
