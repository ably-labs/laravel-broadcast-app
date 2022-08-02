<template>
    <div>
        <ul class="nav nav-tabs nav-fill" id="myTab" role="tablist" v-if="channels.length">
            <li v-for="channel in channels" class="nav-item" role="presentation" :key="'item-' + channel.type + '-' + channel.name">
                <button :class="'nav-link ' + (channel.active ? 'active' : '')" :id="'tab-' + channel.type + '-' + channel.name"
                        data-bs-toggle="tab" :data-bs-target="'#tab-content-' + channel.type + '-' + channel.name" type="button"
                        @click="setActiveChannel(channel)">
                    {{ channel.name }} ({{channel.type}})
                </button>
            </li>

        </ul>
        <div class="tab-content" id="myTabContent" v-if="channels.length">
            <div v-for="channel in channels" :class="'tab-pane fade ' + (channel.active ? 'show active' : '')"
                 :id="'tab-content-' + channel.type + '-' + channel.name"
                 :key="'tab-content-' + channel.type + '-' + channel.name">
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
                        name: channelName,
                        messages: []
                    };

                    this.channels.push(channel);
                    this.setActiveChannel(channel);
                    this.pushStatusMessage(channel, "Subscribed to public channel " + channelName);
                })
                .listenToAll((eventName, data) => {
                    console.log("Event ::  " + eventName + ", data is ::" + JSON.stringify(data));
                })
                .listen('PublicMessageNotification', (data) => {
                    let channel = this.getChannelByName(channelName, 'public');
                    this.pushPublicNotification(channel, data)
                })
                .error((err) => {
                    if('statusCode' in err) {

                        if (err.statusCode === 401)
                            alert("You don't have the access to join this public channel.");
                        else
                            alert("An error occurred while trying to join a public channel, check the console for details.");
                    }

                    console.error(err);
                });
        },

        joinPrivate(event) {
            let channelName = prompt('Enter the private channel name (e.g. room-1, room-2)');
            if (!channelName || channelName.trim().length === 0) {
                return;
            }
            channelName = channelName.trim();

            Echo.private(channelName)
                .subscribed(() => {
                    let channel = {
                        type: 'private',
                        name: channelName,
                        messages: []
                    };

                    this.channels.push(channel);
                    this.setActiveChannel(channel);
                    this.pushStatusMessage(channel, "Subscribed to private channel " + channelName);
                })
                .listenToAll((eventName, data) => {
                    let channel = this.getChannelByName(channelName, 'private');

                    console.log("Event ::  " + eventName + ", data is ::" + JSON.stringify(data));
                })
                .listen('PrivateMessageNotification', (data) => {
                    let channel = this.getChannelByName(channelName, 'private');
                    this.pushPublicNotification(channel, data)
                })
                .listen('.client-message', (data) => {
                    let channel = this.getChannelByName(channelName, 'private');
                    this.pushUserMessage(channel, data.message, data.user);
                })
                .error((err) => {
                    if((typeof err === 'object' && 'statusCode' in err && err.statusCode === 403) || err === 403) {
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
                    let channel = this.getChannelByName(channelName, 'private');

                    if(members.length <= 1)
                        this.pushStatusMessage(channel, "There are no other users in this channel");
                    else
                        this.pushStatusMessage(channel, "There are " + members.length + " users in this channel");

                    channel.memberCount = members.length;
                    console.log("List of members: " + JSON.stringify(members));
                })
                .joining((data) => {
                    let channel = this.getChannelByName(channelName, 'private');

                    if(data && 'name' in data)
                        this.pushStatusMessage(channel, data.name + " joined the channel");
                    else
                        this.pushStatusMessage(channel, "User " + data + " joined the channel");

                    console.log(data, "joined channel")
                })
                .leaving((data) => {
                    let channel = this.getChannelByName(channelName, 'private');

                    if(data && 'name' in data)
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
            let userName = this.userName ? this.userName.trim() : null;
            let message = this.message ? this.message.trim() : null;
            if(!message || !userName)
                return;

            let activeChannelIndex = this.getActiveChannelIndex();

            let channel = this.channels[activeChannelIndex];

            Echo.private(channel.name).whisper('message', {
                user: userName,
                message: message
            });
            this.message = null;
        },

        leaveChannel(event) {
            let activeChannelIndex = this.getActiveChannelIndex();

            let channel = this.channels[activeChannelIndex];
            Echo.leave(channel.name);

            this.channels.splice(activeChannelIndex, 1);

            if (this.channels.length) {
                if (activeChannelIndex === 0) {
                    this.setActiveChannel(this.channels[0]);
                } else
                    this.setActiveChannel(this.channels[activeChannelIndex - 1]);
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
                return obj.name === channelName && obj.type === type;
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
                if (channel.name === this.channels[i].name && channel.type === this.channels[i].type) {
                    this.channels[i].active = true;
                    this.$forceUpdate();
                } else {
                    this.channels[i].active = false;

                }
            }
        },

        scrollToBottom(channel) {
            const container = this.$el.querySelector("#tab-content-" + channel.type + "-" + channel.name + " > .messageContainer");
            if(container){
                setTimeout(function () {
                    container.scrollTop = container.scrollHeight;
                }, 0);
            }
        }
    }

}
</script>
