<?php

use App\Events\PresenceMessageNotification;
use App\Events\PrivateMessageNotification;
use App\Events\PublicMessageNotification;
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
    return view('welcome');
});

Route::get('/public-event', function () {
    return event(new PublicMessageNotification('This is public broadcast message!'));
});

Route::get('/private-event', function () {
    return event(new PrivateMessageNotification('This is private broadcast message!'));
});

Route::get('/presence-event', function () {
    return event(new PresenceMessageNotification('This is presence broadcast message!'));
});

Route::get('/listen', function () {
    return view('listen');
});

Auth::routes(['reset' => false]);
