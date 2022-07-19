<template>
    <div>
        <ul class="nav nav-tabs nav-fill" id="myTab" role="tablist" v-if="channels.length">
            <li v-for="channel in channels" class="nav-item" role="presentation" :key="'item-' + channel.type + '-' + channel.channel">
                <button :class="'nav-link ' + (channel.active ? 'active' : '')" :id="'tab-' + channel.type + '-' + channel.channel"
                        data-bs-toggle="tab" :data-bs-target="'#tab-content-' + channel.type + '-' + channel.channel" type="button"
                        @click="setActiveChannel(channel)">
                    {{ channel.channel }} ({{channel.type}})
                </button>
            </li>

        </ul>
        <div class="tab-content" id="myTabContent" v-if="channels.length">
            <div v-for="channel in channels" :class="'tab-pane fade ' + (channel.active ? 'show active' : '')"
                 :id="'tab-content-' + channel.type + '-' + channel.channel"
                 :key="'tab-content-' + channel.type + '-' + channel.channel">
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
                    <button type="button" class="btn btn-primary" @click="sendMessage">Send message</button>
                </div>
                <div>
                    <button type="button" class="btn btn-danger">Leave channel</button>
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
            channels: [],
            userId: null,
            userName: null,
            message: null
        }
    },

    methods: {
        joinPublic(event) {
            let channelName = prompt('Enter the public channel name (e.g. notification)');
            if (!channelName || channelName.trim().length === 0) {
                return;
            }
            channelName = channelName.trim();

            Echo.channel(channelName)
                .subscribed(() => {
                    let channel = {
                        type: 'public',
                        channel: channelName,
                        messages: []
                    };

                    this.channels.push(channel);
                    this.setActiveChannel(channel);
                    this.pushStatusMessage(channel, "Subscribed to public channel " + channelName);
                })
                .listenToAll((eventName, data) => {
                    console.log("Event ::  " + eventName + ", data is ::" + JSON.stringify(data));

                    let channel = this.getChannelByName(channelName, 'public');
                    this.pushPublicNotification(channel, data)
                })
                .error((err) => {
                    alert("An error occurred while trying to join channel: " + err);
                    console.error(err);
                });
        },

        joinPrivate(event) {
            let channelName = prompt('Enter the private channel name (e.g. chat1, room)');
            if (!channelName || channelName.trim().length === 0) {
                return;
            }
            channelName = channelName.trim();

            Echo.private(channelName)
                .subscribed(() => {
                    let channel = {
                        type: 'private',
                        channel: channelName,
                        messages: []
                    };

                    this.channels.push(channel);
                    this.setActiveChannel(channel);
                    this.pushStatusMessage(channel, "Subscribed to private channel " + channelName);
                })
                .listenToAll((eventName, data) => {
                    let channel = this.getChannelByName(channelName, 'private');

                    console.log("Event ::  " + eventName + ", data is ::" + JSON.stringify(data));
                    if(eventName === '.client-message')
                        this.pushUserMessage(channel, data.message, data.user);
                    else
                        this.pushPublicNotification(channel, data)
                })
                .error((err) => {
                    alert("An error occurred while trying to join channel: " + err);
                    console.error(err);
                });

            Echo.join(channelName)
                .subscribed(()=> {
                    console.log(channelName, "Subscribed to presence channel " + channelName);
                })
                .here((members) => {
                    let channel = this.getChannelByName(channelName, 'private');

                    if(members.length === 1)
                        this.pushStatusMessage(channel, "There are no other users in this channel");
                    else
                        this.pushStatusMessage(channel, "There are " + members.length + " users in this channel");

                    channel.memberCount = members.length;
                    console.log("List of members: " + JSON.stringify(members));
                })
                .joining((info) => {
                    let channel = this.getChannelByName(channelName, 'private');

                    if(info.data !== undefined)
                        this.pushStatusMessage(channel, info.data.name + " joined the channel");
                    else
                        this.pushStatusMessage(channel, "User " + info.clientId + " joined the channel");

                    console.log(info, "joined channel")
                })
                .leaving((info) => {
                    let channel = this.getChannelByName(channelName, 'private');

                    if(info.data !== undefined){
                        this.pushStatusMessage(channel, info.data.name + " left the channel")
                    }
                    else
                        this.pushStatusMessage(channel, "User " + info.clientId + " left the channel")

                    console.log(info, "left channel")
                })
                .listenToAll((eventName, data) => {
                    console.log("Event ::  "+ eventName + ", data is ::" + JSON.stringify(data));
                })
                .error((err)=> {
                    console.error(err)
                })
        },

        sendMessage(event) {
            let userName = this.userName ? this.userName.trim() : null;
            let message = this.message ? this.message.trim() : null;
            if(!message || !userName)
                return;

            let activeChannelIndex = this.getActiveChannelIndex();

            let channel = this.channels[activeChannelIndex];
            let channelName = channel.channel;

            Echo.private(channelName).whisper('message', {
                user: userName,
                message: message
            });
            this.message = null;
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

        pushPublicNotification(channel, data) {
            channel.messages.push({
                type: 'broadcast',
                content: data.message,
                time: new Date()
            });

            this.scrollToBottom(channel);
        },

        getChannelByName(channelName, type) {
            return this.channels.find(obj => {
                return obj.channel === channelName && obj.type === type;
            });
        },

        getActiveChannelIndex() {
            for (let i in this.channels) {
                if (this.channels[i].active) {
                    return parseInt(i);
                }
            }
            return -1;
        },

        setActiveChannel(channel) {
            for (let i in this.channels) {
                if (channel.channel === this.channels[i].channel && channel.type === this.channels[i].type) {
                    this.channels[i].active = true;
                    this.$forceUpdate();
                } else {
                    this.channels[i].active = false;

                }
            }
        },

        scrollToBottom(channel) {
            const container = this.$el.querySelector("#tab-content-" + channel.type + "-" + channel.channel + " > .messageContainer");
            if(container){
                setTimeout(function () {
                    container.scrollTop = container.scrollHeight;
                }, 0);
            }
        }
    }

}
</script>
