<template>
    <div>
        <ul class="nav nav-tabs nav-fill" id="myTab" role="tablist" v-if="tabs.length">
            <li v-for="(channel, index) in tabs" class="nav-item" role="presentation" :key="'item-' + channel.formattedName">
                <button :class="'nav-link ' + (channel === getActiveChannel() ? 'active' : '')" :id="'tab-' + channel.formattedName"
                        data-bs-toggle="tab" :data-bs-target="'#tab-content-' + channel.formattedName" type="button"
                        @click="setActiveChannelIndex(index)">
                    {{ channel.name }} ({{channel.type}})
                </button>
            </li>

        </ul>
        <div class="tab-content mb-3 " id="myTabContent" v-if="tabs.length">
            <div v-for="channel in tabs" :class="'tab-pane fade ' + (channel === getActiveChannel() ? 'show active' : '')"
                 :id="'tab-content-' + channel.formattedName"
                 :key="'tab-content-' + channel.formattedName">
                <div class="messageContainer">
                    <message-component v-for="msg in channel.messages" :key="msg.time.getTime() + '-' + msg.content" :msg="msg"/>
                </div>

                <div v-if="channel.type === 'private'">
                    <div class="mb-2 typingContainer">
                        <span v-if="channel.typingNames.length > 0">{{channel.typingNames.join(', ')}} {{channel.typingNames.length === 1 ? "is" : "are"}} typing...</span>
                    </div>
                    <div v-if="channel.memberCount !== undefined" class="mb-2">
                        Members in chat: {{channel.memberCount}}
                    </div>
                    <input type="text" class="form-control w-100" placeholder="Client name..." v-model="userName" readonly>
                    <input type="text" class="form-control w-100 messageInput" placeholder="Message..." v-model="message" @keydown.enter="publishMessageOnPrivateChannel" @keydown="publishTypingEvent">
                </div>
                <div v-if="channel.type === 'public'">
                    <input type="text" class="form-control w-100 messageInput" placeholder="Message..." v-model="message" @keydown.enter="broadcastMessageOnPublicChannel">
                </div>


                <div class="row mt-3 mb-2">
                    <div class="col-6 text-start">
                        <button v-if="channel.type === 'private'" type="button" class="btn btn-alt" @click="publishMessageOnPrivateChannel">Send client message</button>
                        <button v-if="channel.type === 'public'" type="button" class="btn btn-alt" @click="broadcastMessageOnPublicChannel">Broadcast message</button>
                    </div>
                    <div class="col-6 text-end">
                        <button type="button" class="btn btn-secondary" @click="leaveChannel">Leave room</button>
                    </div>
                </div>

            </div>

        </div>

        <div class="row mx-2">
            <div class="my-2 p-0 col-6 text-start ">
                <button class="btn btn-primary" style="width: 380px; max-width: 90%" @click="subscribeToPublicChannel">Join public room</button>
            </div>
            <div class="my-2 p-0 col-6 text-end">
                <button class="btn btn-primary" style="width: 380px; max-width: 90%" @click="subscribeToPrivateAndPresenceChannel">Join private room</button>
            </div>
        </div>
    </div>

</template>

<script>
export class Channel {
    constructor(props) {
        this.type = props.type;
        this.name = props.name;
        /** @type {Array<Message>} **/
        this.messages = props.messages || [];
        this.typingNames = props.typingNames || [];
        this.typingStopEvents = props.typingStopEvents || []
    }
    get formattedName() {
        return `${this.type}-${this.name}`;
    }
}

export class Message {
    constructor(props) {
        this.type = props.type;
        this.user = props.user;
        this.content = props.content;
        this.time = props.time ?? new Date();
    }
    get formattedName() {
        return `${this.type}-${this.name}`;
    }
}

export default {
    mounted() {
        console.log('Component mounted.')
        if(window.authUser){
            this.userName = window.authUser.name;
            this.userId = window.authUser.id;
        }
    },

    data() {
        return {
            activeIndex: -1,
            /** @type {Array<Channel>} **/
            tabs: [],
            userId: null,
            userName: null,
            message: null,
            throttleTyping: false
        }
    },

    methods: {
        subscribeToPublicChannel(event) {
            this.$fire({
                input : "text",
                title: "Enter the public room name <br> (e.g. notification)",
                buttonsStyling : false,
                showCancelButton : true,
                customClass: {
                    confirmButton: 'btn-alt-confirm',
                    cancelButton : 'btn-secondary',
                    title : 'custom-title'

                },

            }).then(r => {
                let channelName = r.value
                if (channelName?.trim().length === 0) {
                    return;
                }
                channelName = channelName.trim();

                // Register and subscribe to events on the public channel.
                Echo.channel(channelName)
                    .subscribed(() => {
                        const channel = new Channel({
                            type: 'public',
                            name: channelName
                        });

                        this.tabs.push(channel);
                        this.setActiveChannelIndex(this.tabs.length - 1);
                        this.updateStatusMessageOnUI(channel, "Subscribed to public room " + channelName);
                    })
                    .listenToAll((eventName, data) => {
                        console.log("Event ::  " + eventName + ", data is ::" + JSON.stringify(data));
                    })
                    .listen('PublicMessageEvent', (data) => {
                        const channel = this.getChannelByName(channelName, 'public');
                        this.updateBroadcastNotificationOnUI(channel, data)
                    })
                    .error((err) => {
                        if (err?.statusCode === 401)
                            alert("You don't have the access to join this public room.");
                        else
                            alert("An error occurred while trying to join a public room, check the console for details.");

                        console.error(err);
                    });
            });
        },

        subscribeToPrivateAndPresenceChannel(event) {
            if(!window.authUser)
                this.$fire({
                    title: 'You are a guest user, please sign up for joining private channels.',
                    buttonsStyling : false,
                    showCancelButton : true,
                    confirmButtonText : "Yes, I want to register",
                    cancelButtonText : "Stay on the homepage",
                    customClass: {
                        confirmButton: 'btn-alt-confirm btn-confirm-custom-event',
                        cancelButton : 'btn-secondary',
                        title : 'custom-title'

                    }}).then(function (result) {
                    if (result.value) {
                        window.location = "/register"
                    }
                });
            else
            this.$fire({
                input : "text",
                title: "Enter the private room name <br>  (e.g. room-1, room-2)",
               buttonsStyling : false,
               showCancelButton : true,
               customClass: {
                   confirmButton: 'btn-alt-confirm',
                   cancelButton : 'btn-secondary',
                   title : 'custom-title'

               },

            }).then(r => {
                let channelName = r.value
                if (!channelName?.trim().length) {
                    return;
                }
                channelName = channelName.trim();

                // Register and subscribe to events on the private channel.
                Echo.private(channelName)
                    .subscribed(() => {
                        const channel = new Channel({
                            type: 'private',
                            name: channelName
                        });

                        this.tabs.push(channel);
                        this.setActiveChannelIndex(this.tabs.length - 1);
                        this.updateStatusMessageOnUI(channel, "Subscribed to private room " + channelName);
                    })
                    .listenToAll((eventName, data) => {
                        console.log("Event ::  " + eventName + ", data is ::" + JSON.stringify(data));
                    })
                    .listen('PrivateMessageEvent', (data) => {
                        const channel = this.getChannelByName(channelName, 'private');
                        this.updateBroadcastNotificationOnUI(channel, data)
                    })
                    .listenForWhisper('message', (data) => {
                        const channel = this.getChannelByName(channelName, 'private');
                        this.updateUserMessageOnUI(channel, data.message, data.user);
                    })
                    .listenForWhisper('typing', (data) => {
                        let user = data.user;
                        if(!user)
                            return;

                        const channel = this.getChannelByName(channelName, 'private');
                        clearTimeout(channel.typingStopEvents[user]);

                        if(!channel.typingNames.includes(user))
                            channel.typingNames.push(user);

                        channel.typingStopEvents[user] = setTimeout(() => {
                            delete channel.typingStopEvents[user];
                            channel.typingNames = channel.typingNames.filter(e => e !== user);
                        }, 1500);

                    })
                    .error((err) => {
                        if( err === 403 || err?.statusCode === 403) {
                            if(!window.authUser)
                                this.$fire({
                                    title: 'You don\'t have the access to join this private room, try logging into the application.',
                                    buttonsStyling : false,
                                    showCancelButton : true,
                                    customClass: {
                                        confirmButton: 'btn-alt-confirm',
                                        cancelButton : 'btn-secondary',
                                        title : 'custom-title'

                                    }});
                            else
                                this.$fire({
                                    title: 'Please enter room name in the format room-1, room-2, room-3 etc. Other rooms are not accessible',
                                    buttonsStyling : false,
                                    showCancelButton : true,
                                    customClass: {
                                        confirmButton: 'btn-alt-confirm',
                                        cancelButton : 'btn-secondary',
                                        title : 'custom-title'

                                    }});
                        }
                        else
                            alert("An error occurred while trying to join a private room, check the console for details.");

                        console.error(err);
                    });

                // Register and subscribe to presence changes on presence channel.
                Echo.join(channelName)
                    .subscribed(()=> {
                        console.log(channelName, "Subscribed to presence room " + channelName);
                    })
                    .here((members) => {
                        const channel = this.getChannelByName(channelName, 'private');

                        if(members.length <= 1)
                            this.updateStatusMessageOnUI(channel, "There are no other users in this room");
                        else
                            this.updateStatusMessageOnUI(channel, "There are " + members.length + " users in this room");

                        channel.memberCount = members.length;
                        console.log("List of members: " + JSON.stringify(members));
                    })
                    .joining((data) => {
                        const channel = this.getChannelByName(channelName, 'private');

                        if (data?.name)
                            this.updateStatusMessageOnUI(channel, data.name + " joined the room");
                        else
                            this.updateStatusMessageOnUI(channel, "User " + data + " joined the room");

                        console.log(data, "joined room");
                    })
                    .leaving((data) => {
                        const channel = this.getChannelByName(channelName, 'private');

                        if (data?.name)
                            this.updateStatusMessageOnUI(channel, data.name + " left the room")
                        else
                            this.updateStatusMessageOnUI(channel, "User " + data + " left the room")

                        console.log(data, "left room");
                    })
                    .listenToAll((eventName, data) => {
                        console.log("Event ::  "+ eventName + ", data is ::" + JSON.stringify(data));
                    })
                    .error((err)=> {
                        console.error(err)
                    })
           });

        },

        // Uses to send message to other clients using client-events.
        publishMessageOnPrivateChannel(event) {
            const userName = this.userName?.trim();
            const message = this.message?.trim();
            if(!message || !userName)
                return;

            const channel = this.getActiveChannel();

            Echo.private(channel.name).whisper('message', {
                user: userName,
                message: message
            }, (err) => {
                if(!err && !Echo.options.echoMessages) {
                    this.updateUserMessageOnUI(channel, message, userName);
                }
            });
            this.message = null;
        },

        /**
         * @param event KeyboardEvent
         */
        publishTypingEvent(event) {
            if (this.throttleTyping || event.key.length !== 1)
                return;

            this.throttleTyping = true;

            const userName = this.userName?.trim();
            const channel = this.getActiveChannel();
            Echo.private(channel.name)
                .whisper('typing', { user: userName });

            setTimeout(() => {
                this.throttleTyping = false;
            }, 1000);
        },

        // Message is published/broadcasted using laravel public API endpoint - /api/public-event
        broadcastMessageOnPublicChannel() {
            const message = this.message?.trim();
            if(!message)
                return;

            const publicChannelName = this.getActiveChannel().name;

            const broadcastUrl = window.location.origin + "/api/public-event";
            axios.post(broadcastUrl, { channelName : publicChannelName , message });

            this.message = null;
        },

        // Unsubscribe and leaves the channel
        leaveChannel(event) {
            const channel = this.getActiveChannel();
            Echo.leave(channel.name);

            this.tabs.splice(this.activeIndex, 1);

            if (this.tabs.length) {
                if (this.activeIndex === 0) {
                    this.setActiveChannelIndex(0);
                } else
                    this.setActiveChannelIndex(this.activeIndex - 1);
            }
        },

        updateStatusMessageOnUI(channel, message) {
            channel.messages.push(new Message({
                type: 'status',
                content: message
            }))

            this.scrollToBottom();
        },

        updateUserMessageOnUI(channel, message, user) {
            channel.messages.push(new Message({
                type: 'user',
                user: user,
                content: message
            }));

            this.scrollToBottom();
        },

        updateBroadcastNotificationOnUI(channel, data) {
            channel.messages.push(new Message({
                type: 'broadcast',
                content: data.message
            }));

            this.scrollToBottom();
        },

        getChannelByName(channelName, type) {
            return this.tabs.find(obj => {
                return obj.name === channelName && obj.type === type;
            });
        },

        getActiveChannel() {
            return this.tabs[this.activeIndex];
        },

        setActiveChannelIndex(index) {
            this.activeIndex = index;
            this.focusMessageInput();
        },

        scrollToBottom() {
            this.$nextTick(() => {
                const container = this.$el.querySelector("#myTabContent > .tab-pane.active > .messageContainer");
                if(container){
                    container.scrollTop = container.scrollHeight;
                }
            });
        },

        focusMessageInput() {
            this.$nextTick(() => {
                const input = this.$el.querySelector("#myTabContent > .tab-pane.active .messageInput");
                console.log(input);
                if (input) {
                    input.focus();
                }
            });
        }
    }

}
import Vue from "vue"
import VueSimpleAlert from "vue-simple-alert";

Vue.use(VueSimpleAlert);
</script>
