window._ = require('lodash');

try {
    require('bootstrap');
} catch (e) {}

/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

window.axios = require('axios');

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */
import Echo from 'laravel-echo';

const urlParams = new URLSearchParams(window.location.search);
const usePusher = urlParams.get('pusher');

if(parseInt(usePusher) === 1) {
    window.Pusher = require('pusher-js');

    window.Echo = new Echo({
        broadcaster: 'pusher',
        key: process.env.MIX_ABLY_KEY.split(':')[0], // Use first part of the API key before : (colon)
        wsHost: 'realtime-pusher.ably.io',
        wsPort: 443,
        disableStats: false,
        encrypted: true,
    });

    window.Echo.connector.pusher.connection.bind('connected', () => {
        console.log('connected to ably via pusher-js');
    });

    window.Echo.connector.pusher.connection.bind('disconnected', () => {
        console.log('disconnected from ably');
    });

    window.Echo.connector.pusher.connection.bind("error",  (err) => {
        console.error('connection error', err);
    });

    window.Echo.connector.pusher.bind_global((eventName, data)=> {
        console.log("Global eventName :: " + eventName + " data :: " + JSON.stringify(data));
    });
}
else {
    window.Ably = require('ably');

    window.Echo = new Echo({
        broadcaster: 'ably',
    });

    window.Echo.connector.ably.connection.on((stateChange) => {
        console.log("LOGGER:: Connection event :: ", stateChange);
        if (stateChange.current === 'disconnected' && stateChange.reason?.code === 40142) { // key/token status expired
            console.log("LOGGER:: Connection token expired https://help.ably.io/error/40142");
        }
    });
}
