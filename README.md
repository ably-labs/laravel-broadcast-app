# Ably Laravel Broadcast App

A demo chat application written in Vue.js for testing out the Ably Broadcaster for Laravel.

The application features the following:
* Laravel built-in user authentication (login, registration)
* Public chat rooms (no client permission to send messages, only broadcast supported)
* Private chat rooms (chat between several participants)
* Presence channels (visible join/leave events and the number of participants)
* Sample implementation of a typing indicator
* Joining multiple chat rooms simultaneously

## Requirements

This application uses Laravel 8, which requires PHP version 7.3 or greater.

## Setup

1. Run `composer install` and `npm install` after cloning the project
2. Create an `.env` file based on `.env.example`
3. Set your `ABLY_KEY` and make sure the database credentials are correct
4. Run `php artisan serve` in a terminal window to start the Laravel backend
5. Run `npm run watch` in another terminal window
6. Open http://127.0.0.1:8000 to access the demo app

## Usage

1. Register one or more accounts in the Laravel app, based on your testing requirements.
2. <b>a)</b> If joining a public channel, you can join use any channel name
   <br />
   <b>b)</b> If you're authenticated into the Laravel app, you can join a private channel. The required channel prefix is `room-<id>`, as defined in `routes/channels.php` 
3. <b>a)</b> In public channels, you can broadcast messages via Laravel Ably Broadcaster (server-side)
   <br />
   <b>b)</b> In private channels, messages are sent via Laravel Echo from client-side. In addition, the typing indicator is visible when applicable.

## Screenshots

Public channel example, after sending out a server-side broadcast request
<img src="docs/images/public_channel.png" alt="Public channel example">

Private channel example with presence events (joining and user count)
<img src="docs/images/private_channel.png" alt="Public channel example">
