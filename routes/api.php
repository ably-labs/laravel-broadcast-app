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

// Public broadcast for a guest user.
// Throttle to 60 requests per 1 minute per machine
// This is generally to prevent DOS/DDOS/message spamming from happening
// https://dev.to/aliadhillon/new-simple-way-of-creating-custom-rate-limiters-in-laravel-8-65n
Route::middleware('throttle:60,1')->get('/public-event', function (Request $request) {
    broadcast(new PublicMessageEvent($request->channel, $request->message));
});

// Private broadcast for a authenticated user.
// This alternative option to send messages is slow compared to client-events 
// since it goes through laravel before being sent to ably.
// This allows message filtering, throttling and persistent storage before being sent.
Route::get('/private-event', function (Request $request) {
    if($request->input('to_others')) {
        broadcast(new PrivateMessageEvent($request->channel, $request->message))->toOthers();
    } else {
        broadcast(new PrivateMessageEvent($request->channel, $request->message));
    }
})->middleware('auth'); // Only authenticated users are allowed (https://laravel.com/docs/authentication#protecting-routes)
