# Ably Laravel Broadcast App

Demo web-chat app using Ably Broadcaster+Echo based on laravel

## Features
* Public rooms for a guest user.
* Laravel built-in user authentication (login, registration).
* Public chat rooms (no client permission to send messages, only broadcast supported).
* Private chat rooms (chat between several participants).
* Presence channels (visible join/leave events and the number of participants).
* Sample implementation of a typing indicator.
* Joining multiple chat rooms simultaneously.

## Requirements
1. PHP version >= 7.3
2. Node.js >= 14.x.x

## Setup

1. Clone the repository
```
git clone https://github.com/ably-labs/laravel-broadcast-app
```
2. Install dependencies
```
composer install
npm install
```
3. Create `.env` file, and copy contents from `.env.example`, set `ABLY_KEY`
```
ABLY_KEY=ROOT_API_KEY_COPIED_FROM_ABLY_WEB_DASHBOARD
```
4. Start laravel backend server
```
php artisan serve 
```
5. Start UI server in watch/hot-reloading mode
```
npm run watch
```
6. Access the web app via http://127.0.0.1:8000

## Usage
The web app runs in two modes

**1. Guest Mode ( Only public rooms can be created / accessed)**
- Use room name in any format.
- In public rooms, messages are published via server (client can't publish messages via echo).

**2. User Mode ( Private rooms can be created / accessed)**
- If user signed up and logged in into the Laravel app, it can join a private room. The required room prefix is `room-<id>`, as defined in `routes/rooms.php`
- In private rooms, messages are published via Laravel Echo from client-side. In addition, the typing indicator is visible when applicable.

## Screenshots

**Public room**

<img src="docs/images/public_room.png" alt="Public room example">

**Private room**

<img src="docs/images/private_room.png" alt="Public room example">
