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
        <div class="tab-content" id="myTabContent" v-if="tabs.length">
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
                    <input type="text" class="form-control w-100 messageInput" placeholder="Message..." v-model="message" @keydown.enter="sendMessage" @keydown="typingStart">
                </div>
                <div v-if="channel.type === 'public'">
                    <input type="text" class="form-control w-100 messageInput" placeholder="Message..." v-model="message" @keydown.enter="broadcastMessage">
                </div>


                <div class="chatButtonContainer">
                    <button v-if="channel.type === 'private'" type="button" class="btn btn-alt" @click="sendMessage">Send client message</button>
                    <button v-if="channel.type === 'public'" type="button" class="btn btn-alt" @click="broadcastMessage">Broadcast message</button>
                    <button type="button" class="btn btn-secondary" @click="leaveChannel">Leave channel</button>
                </div>

            </div>
            <hr />

        </div>

        <div class="my-2">
            <button class="btn btn-primary" @click="joinPublic">Join public channel</button>
            <button class="btn btn-primary" @click="joinPrivate">Join private channel</button>
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
        joinPublic(event) {
            let channelName = prompt('Enter the public channel name (e.g. notification)');
            if (channelName?.trim().length === 0) {
                return;
            }
            channelName = channelName.trim();

            Echo.channel(channelName)
                .subscribed(() => {
                    const channel = new Channel({
                        type: 'public',
                        name: channelName
                    });

                    this.tabs.push(channel);
                    this.setActiveChannelIndex(this.tabs.length - 1);
                    this.pushStatusMessage(channel, "Subscribed to public channel " + channelName);
                })
                .listenToAll((eventName, data) => {
                    console.log("Event ::  " + eventName + ", data is ::" + JSON.stringify(data));
                })
                .listen('PublicMessageNotification', (data) => {
                    const channel = this.getChannelByName(channelName, 'public');
                    this.pushBroadcastNotification(channel, data)
                })
                .error((err) => {
                    if (err?.statusCode === 401)
                        alert("You don't have the access to join this public channel.");
                    else
                        alert("An error occurred while trying to join a public channel, check the console for details.");

                    console.error(err);
                });
        },

        joinPrivate(event) {
            let channelName = prompt('Enter the private channel name (e.g. room-1, room-2)');
            if (!channelName?.trim().length) {
                return;
            }
            channelName = channelName.trim();

            Echo.private(channelName)
                .subscribed(() => {
                    const channel = new Channel({
                        type: 'private',
                        name: channelName
                    });

                    this.tabs.push(channel);
                    this.setActiveChannelIndex(this.tabs.length - 1);
                    this.pushStatusMessage(channel, "Subscribed to private channel " + channelName);
                })
                .listenToAll((eventName, data) => {
                    console.log("Event ::  " + eventName + ", data is ::" + JSON.stringify(data));
                })
                .listen('PrivateMessageNotification', (data) => {
                    const channel = this.getChannelByName(channelName, 'private');
                    this.pushBroadcastNotification(channel, data)
                })
                .listenForWhisper('message', (data) => {
                    const channel = this.getChannelByName(channelName, 'private');
                    this.pushUserMessage(channel, data.message, data.user);
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
                            alert("You don't have the access to join this private channel, try logging into the application.");
                        else
                            alert("You don't have the access to join this private channel, try entering the channel room-1 or room-2");
                    }
                    else
                        alert("An error occurred while trying to join a private channel, check the console for details.");

                    console.error(err);
                });

            Echo.join(channelName)
                .subscribed(()=> {
                    console.log(channelName, "Subscribed to presence channel " + channelName);
                })
                .here((members) => {
                    const channel = this.getChannelByName(channelName, 'private');

                    if(members.length <= 1)
                        this.pushStatusMessage(channel, "There are no other users in this channel");
                    else
                        this.pushStatusMessage(channel, "There are " + members.length + " users in this channel");

                    channel.memberCount = members.length;
                    console.log("List of members: " + JSON.stringify(members));
                })
                .joining((data) => {
                    const channel = this.getChannelByName(channelName, 'private');

                    if (data?.name)
                        this.pushStatusMessage(channel, data.name + " joined the channel");
                    else
                        this.pushStatusMessage(channel, "User " + data + " joined the channel");

                    console.log(data, "joined channel")
                })
                .leaving((data) => {
                    const channel = this.getChannelByName(channelName, 'private');

                    if (data?.name)
                        this.pushStatusMessage(channel, data.name + " left the channel")
                    else
                        this.pushStatusMessage(channel, "User " + data + " left the channel")

                    console.log(data, "left channel")
                })
                .listenToAll((eventName, data) => {
                    console.log("Event ::  "+ eventName + ", data is ::" + JSON.stringify(data));
                })
                .error((err)=> {
                    console.error(err)
                })
        },

        sendMessage(event) {
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
                    this.pushUserMessage(channel, message, userName);
                }
            });
            this.message = null;
        },

        /**
         * @param event KeyboardEvent
         */
        typingStart(event) {
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

        broadcastMessage(event) {
            const message = this.message?.trim();
            if(!message)
                return;

            const channel = this.getActiveChannel().name;

            const broadcastUrl = window.location.origin + "/public-event";
            axios.get(broadcastUrl, { params: { message, channel}});

            this.message = null;
        },

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

        pushStatusMessage(channel, message) {
            channel.messages.push(new Message({
                type: 'status',
                content: message
            }))

            this.scrollToBottom();
        },

        pushUserMessage(channel, message, user) {
            channel.messages.push(new Message({
                type: 'user',
                user: user,
                content: message
            }));

            this.scrollToBottom();
        },

        pushBroadcastNotification(channel, data) {
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
</script>
