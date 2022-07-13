<template>
    <div>
        <ul class="nav nav-tabs nav-fill" id="myTab" role="tablist">
            <li v-for="item in items" class="nav-item" role="presentation">
                <button :class="'nav-link ' + (item.active ? 'active' : '')" :id="'tab-' + item.channel"
                        data-bs-toggle="tab" :data-bs-target="'#tab-content-' + item.channel" type="button">
                    {{ item.channel }}
                </button>
            </li>

        </ul>
        <div class="tab-content" id="myTabContent">
            <div v-for="item in items" :class="'tab-pane fade ' + (item.active ? 'show active' : '')"
                 :id="'tab-content-' + item.channel">
                <div class="messageContainer">
                    <div v-for="msg in item.messages">
                        <span v-if="msg.time instanceof Date">[{{ msg.time.toLocaleTimeString() }}]</span>
                        <span v-if="msg.type === 'status'"><i>{{ msg.content }}</i></span>
                        <span v-else-if="msg.type === 'public'"><b>{{ msg.content }}</b></span>
                        <span v-else>{{ msg }}</span>
                    </div>
                </div>
                <input type="text" class="form-control w-100" placeholder="Message...">
                <button type="button" class="btn btn-primary">Send</button>
            </div>

        </div>

        <div class="my-2">
            <button class="btn btn-primary" @click="joinPublic">Join public channel</button>
            <button class="btn btn-primary">Join private channel</button>
        </div>
    </div>

</template>

<script>
export default {
    mounted() {
        console.log('Component mounted.')
    },

    data() {
        return {
            items: [],
            activeChannel: -1
        }
    },

    methods: {
        joinPublic(event) {
            let channelName = prompt('Enter the public channel name (e.g. notification)').trim();
            if (!channelName) {
                return;
            }

            Echo.channel(channelName)
                .subscribed(() => {
                    this.items.push({
                        channel: channelName,
                        messages: []
                    })
                    this.setActiveChannel(channelName);
                    this.pushStatusMessage(channelName, "Subscribed to public channel " + channelName);
                })
                .listenToAll((eventName, data) => {
                    console.log("Event ::  " + eventName + ", data is ::" + JSON.stringify(data));

                    this.pushPublicNotification(channelName, data)
                })
                .error((err) => {
                    console.error(err)
                });
        },

        pushStatusMessage(channelName, message) {
            const channel = this.getChannelByName(channelName);
            if (channel) {
                channel.messages.push({
                    type: 'status',
                    content: message,
                    time: new Date()
                })
                console.log(message);
            }
        },

        pushPublicNotification(channelName, data) {
            const channel = this.getChannelByName(channelName);

            if (channel) {
                channel.messages.push({
                    type: 'public',
                    content: data.message,
                    time: new Date()
                });

                const container = this.$el.querySelector("#tab-content-" + channelName + " > .messageContainer");
                setTimeout(function () {
                    container.scrollTop = container.scrollHeight;
                }, 0);
            }
        },

        getChannelByName(channelName) {
            return this.items.find(obj => {
                return obj.channel === channelName;
            });
        },

        setActiveChannel(channelName) {
            for (let i in this.items) {
                if (channelName === this.items[i].channel) {
                    this.activeChannelIndex = i;
                    this.items[i].active = true;
                } else {
                    this.items[i].active = false;
                }
            }
        }

    }

}
</script>
