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
// 1. Through laravel to ably [ client1 -> laravel -> ably -> client2]
// 2. Through client-events [ client1 -> ably -> client2]
// Publishing via client-events is faster than through laravel, since there is no intermediate party involved.
// Read more about client-events => https://laravel.com/docs/broadcasting#client-events

// Public broadcast for a guest user.
// Throttle to 60 requests/1 minute per machine.
// This is generally to prevent DOS attack/message spamming on public channels.
// https://dev.to/aliadhillon/new-simple-way-of-creating-custom-rate-limiters-in-laravel-8-65n.
Route::middleware('throttle:60,1')->get('/public-event', function (Request $request) {
    broadcast(new PublicMessageEvent($request->channel, $request->message));
});

// Private broadcast for an authenticated user.
Route::get('/private-event', function (Request $request) {
    if($request->input('to_others')) {
        broadcast(new PrivateMessageEvent($request->channel, $request->message))->toOthers();
    } else {
        broadcast(new PrivateMessageEvent($request->channel, $request->message));
    }
})->middleware('auth'); // Only authenticated users are allowed (https://laravel.com/docs/authentication#protecting-routes)
