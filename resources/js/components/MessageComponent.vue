<template>
    <div>
        <span v-if="msg.time instanceof Date">[{{ msg.time.toLocaleTimeString() }}]</span>
        <span v-if="msg.type === 'status'"><i>{{ msg.content }}</i></span>
        <span v-else-if="msg.user"><b>{{ msg.user }}</b>: {{msg.content}}</span>
        <span v-else-if="msg.type === 'broadcast'"><b>{{ msg.content }}</b></span>
        <span v-else>{{ msg }}</span>
    </div>
</template>

<script>
import {Message} from "./ChatComponent";

export default {
    props: {
        msg: {
            type: Message
        }
    }
}
</script>
