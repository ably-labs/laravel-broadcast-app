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
                    <div v-for="msg in channel.messages">
                        <span v-if="msg.time instanceof Date">[{{ msg.time.toLocaleTimeString() }}]</span>
                        <span v-if="msg.type === 'status'"><i>{{ msg.content }}</i></span>
                        <span v-else-if="msg.user"><b>{{ msg.user }}</b>: {{msg.content}}</span>
                        <span v-else-if="msg.type === 'broadcast'"><b>{{ msg.content }}</b></span>
                        <span v-else>{{ msg }}</span>
                    </div>
                </div>

                <div v-if="channel.type === 'private'">
                    <div v-if="channel.memberCount !== undefined" class="mb-2">
                        Members in chat: {{channel.memberCount}}
                    </div>
                    <input type="text" class="form-control w-100" placeholder="Client name..." v-model="userName" readonly>
                    <input type="text" class="form-control w-100" placeholder="Message..." v-model="message">
                    <button type="button" class="btn btn-primary" @click="sendMessage">Send client message</button>
                </div>
                <div v-if="channel.type === 'public'">
                    <input type="text" class="form-control w-100" placeholder="Message..." v-model="message">
                    <button type="button" class="btn btn-success" @click="broadcastMessage">Broadcast message</button>
                </div>
                <div>
                    <button type="button" class="btn btn-danger" @click="leaveChannel">Leave channel</button>
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
        this.messages = props.messages;
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
            message: null
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
                        name: channelName,
                        messages: []
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
                        name: channelName,
                        messages: []
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
            channel.messages.push({
                type: 'status',
                content: message,
                time: new Date()
            })

            this.scrollToBottom(channel);
        },

        pushUserMessage(channel, message, user) {
            channel.messages.push({
                type: 'user',
                user: user,
                content: message,
                time: new Date()
            });

            this.scrollToBottom(channel);
        },

        pushBroadcastNotification(channel, data) {
            channel.messages.push({
                type: 'broadcast',
                content: data.message,
                time: new Date()
            });

            this.scrollToBottom(channel);
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
        },

        scrollToBottom(channel) {
            const container = this.$el.querySelector("#tab-content-" + channel.formattedName + " > .messageContainer");
            if(container){
                setTimeout(function () {
                    container.scrollTop = container.scrollHeight;
                }, 0);
            }
        }
    }

}
</script>
