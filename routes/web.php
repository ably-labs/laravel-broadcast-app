<?php

use App\Events\PresenceMessageNotification;
use App\Events\PrivateMessageNotification;
use App\Events\PublicMessageNotification;
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
    return view('listen');
});

Route::get('/public-event', function (Request $request) {
    event(new PublicMessageNotification($request->channel, $request->message));
});

Route::get('/private-event', function (Request $request) {
    if($request->input('to_others'))
        broadcast(new PrivateMessageNotification($request->channel, $request->message))->toOthers();
    else
        event(new PrivateMessageNotification($request->channel, $request->message));
});

Route::get('/presence-event', function (Request $request) {
    event(new PresenceMessageNotification($request->channel, $request->message));
});

Auth::routes(['reset' => false]);
