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

// User authentication is allowed for private or presence channel returning truthy value and denied for falsy value.
// Presence channels are private channels with additional presence functionality.
// https://laravel.com/docs/broadcasting#authorizing-presence-channels
Broadcast::channel('room-{roomId}', function ( User $user, $roomId) { 
    if($user->canJoinRoom($roomId))
        return ['id' => $user->id, 'name' => $user->name, 'ably-capability' => ["subscribe", "history", "channel-metadata", "presence", "publish"]];

    return false;
});
