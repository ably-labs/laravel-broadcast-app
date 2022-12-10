<?php

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

Route::get('/public-event', function (Request $request) {
    event(new PublicMessageEvent($request->channel, $request->message));
});

Route::get('/private-event', function (Request $request) {
    if($request->input('to_others'))
        broadcast(new PrivateMessageEvent($request->channel, $request->message))->toOthers();
    else
        event(new PrivateMessageEvent($request->channel, $request->message));
});

Route::get('/presence-event', function (Request $request) {
    event(new PresenceMessageEvent($request->channel, $request->message));
});
