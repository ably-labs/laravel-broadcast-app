# Chat app using Ably Broadcaster+Echo

Demo web-chat app using Ably Broadcaster+Echo based on laravel.

## Features
* Public chat rooms for a guest user.
* Laravel built-in user authentication (registration + login).
* Private chat rooms with presence for authenticated users.
* Typing indicator for private rooms.
* Join multiple rooms simultaneously.

Note - Pusher mode uses legacy AblyBroadcaster + Pusher client and not recommended for use.

## Requirements
1. PHP version >= 7.3.
2. Node.js >= 14.x.x.

## Setup

1. Clone the repository.
```
git clone https://github.com/ably-labs/laravel-broadcast-app
```
2. Install dependencies.
```
composer install
npm install
```
3. Create `.env` file, and copy contents from `.env.example`, set `ABLY_KEY`.
```
ABLY_KEY=ROOT_API_KEY_COPIED_FROM_ABLY_WEB_DASHBOARD
```
4. Start laravel backend server.
```
php artisan serve 
```
5. Start UI server in watch/hot-reloading mode.
```
npm run watch
```
6. Access the web app via http://127.0.0.1:8000.

## Usage
The web app works in two modes -

**1. Guest Mode ( Only public rooms can be created / joined)**
- Use room name in any format.
- In public rooms, messages are published via server (client can't publish messages via echo).

**2. User Mode ( Private rooms can be created / joined)**
- If user signed up and logged in into the Laravel app, it can create/join a private room.
- The required room prefix is `room-<id>`, as defined in `routes/rooms.php`.
- In private rooms, messages are published via Laravel Echo from client-side. 

## Screenshots

**Public room**

<img src="docs/images/public_room.png" alt="Public room example">

**Private room**

<img src="docs/images/private_room.png" alt="Private room example">

**User registration**

<img src="docs/images/registration.png" alt="User registration example">
