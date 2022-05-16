/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');

window.Vue = require('vue').default;

/**
 * The following block of code may be used to automatically register your
 * Vue components. It will recursively scan this directory for the Vue
 * components and automatically register them with their "basename".
 *
 * Eg. ./components/ExampleComponent.vue -> <example-component></example-component>
 */

// const files = require.context('./', true, /\.vue$/i)
// files.keys().map(key => Vue.component(key.split('/').pop().split('.')[0], files(key).default))

Vue.component('example-component', require('./components/ExampleComponent.vue').default);

/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

const app = new Vue({
    el: '#app',
    created() {

        // subscription to public, private and presence channels
        Echo.channel('notification')
            .subscribed(()=> {
                console.log("Subscribed to public channel notification")
            })
            .listenToAll((eventName, data) => {
                console.log("Event ::  "+ eventName + ", data is ::" + JSON.stringify(data));
            })
            .error((err)=> {
                console.error(err)
            });

        Echo.private('notification')
            .subscribed(()=> {
                console.log("Subscribed to private channel notification");
            })
            .listenToAll((eventName, data) => {
                console.log("Event ::  "+ eventName + ", data is ::" + JSON.stringify(data));
            })
            .error((err)=> {
                console.error(err)
            });

        Echo.join('room')
            .subscribed(()=> {
                console.log("Subscribed to presence channel room");
            })
            .here((members) => {
                console.log("Total members are " + JSON.stringify(members));
            })
            .joining((info) => {
                console.log(info, "joined channel")
            })
            .leaving((info) => {
                console.log(info, "left channel")
            })
            .listenToAll((eventName, data) => {
                console.log("Event ::  "+ eventName + ", data is ::" + JSON.stringify(data));
            })
            .error((err)=> {
                console.error(err)
            })

        // subscribe to whisper and listen
        Echo.private(`chat1`)
            .subscribed(()=> {
                console.log("Subscribed to public channel chat1 for listen whisper")
            })
            .error((err)=> {
                console.error(err)
            })
            .listenToAll((eventName, data) => {
                console.log("Event ::  "+ eventName + ", data is ::" + JSON.stringify(data));
            })
            .listenForWhisper('typing', (e) => {
                console.log(e.name);
            });

        // triggering client events via channels
        Echo.private(`chat1`)
            .subscribed(()=> {
                console.log("Subscribed to public channel chat1 for publish whisper")
            })
            .error((err)=> {
                console.error(err)
            })
            .whisper('typing', {
                name: 'sac'
            });
    }
});
