<?php

use App\Events\PrivateMessageEvent;
use App\Events\PublicMessageEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Important Note -
// Publishing message through laravel to ably allows request throttling, and filtering + persistent storage for messages.
// Public channel allows to publish messages only through laravel to ably.
// Private/Presence channels allows to send/publish messages in two ways 
// 1. Through laravel to ably [ echo-client1 -> laravel -> ably -> echo-client2, echo-client3, echo-client4... ]
// 2. Through client-events [ echo-client1 -> ably -> echo-client2, echo-client3, echo-client4... ]
// Publishing via client-events is faster than through laravel, since there is no intermediate party involved.
// Read more about client-events => https://laravel.com/docs/broadcasting#client-events

// Public broadcast for a guest user.
// Throttle to 60 requests/1 minute per ip address.
// This is generally to prevent DOS attack/message spamming on public channels.
// If using reverse proxy, make sure to configure IP properly, since all incoming requests will have same IP address.
// https://dev.to/aliadhillon/new-simple-way-of-creating-custom-rate-limiters-in-laravel-8-65n.
Route::post('/public-event', function (Request $request) {
    $channelName = $request->post('channelName');
    $message = $request->post('message');
    broadcast(new PublicMessageEvent( $channelName, $message ));
})->middleware('throttle:60,1'); // 60 requests/minute are allowed.

// Private broadcast for an authenticated user.
// If request throttling is enabled, it will be per user session instead of ip adddress.
// This route is currently unused, client-events are used instead.
Route::post('/private-event', function (Request $request) {
    $channelName = $request->post('channelName');
    $message = $request->post('message');
    if($request->input('to_others')) {
        broadcast(new PrivateMessageEvent($channelName, $message))->toOthers();
    } else {
        broadcast(new PrivateMessageEvent($channelName, $message));
    }
})->middleware('auth'); // Only authenticated users are allowed (https://laravel.com/docs/authentication#protecting-routes)
