<?php

use App\Events\PresenceMessageEvent;
use App\Events\PrivateMessageEvent;
use App\Events\PublicMessageEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('ChatRoom');
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

Auth::routes(['reset' => false]);
