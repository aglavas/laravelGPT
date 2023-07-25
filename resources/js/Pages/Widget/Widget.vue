<script setup>
import { computed, ref, watch, onMounted } from 'vue';
import { useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    conversation: Object
})

const messages = computed(() => props.conversation?.messages ?? []);
const loading = ref(false);
const open = ref(false);
const form = useForm({
    prompt: ''
});
const submitForm = () => {
  form.post(route('widget.conversation.prompt', props.conversation?.id ?? 'new'), {
      preserveScroll: true,
      Success: () => form.reset()
  })
};

const setupEcho = () => {
    if (!props.conversation) return;
    console.log(props.conversation);
    window.Echo.channel("conversations."+ props.conversation.id + ".messages")
        .stopListening('.PromptResponseStarted')
        .stopListening('.PromptResponseUpdated')
        .listen('.PromptResponseStarted', (event) => {
            console.log(event);
            loading.value = true;
        })
        .listen('.PromptResponseUpdated', (event) => {
            console.log(event);
            messages.value.push({
                id: event.id,
                content: event.content,
                role: event.role
            });
            loading.value = false;
        });
}

watch(() => props.conversation, () => {
    setupEcho();
    if (!props.conversation) return;
    localStorage.setItem('conversation_id', props.conversation.id);
})

onMounted(() => {
    setupEcho();
    if (!props.conversation) {
        const conversationId = localStorage.getItem('conversation_id');
        if (conversationId) {
            router.visit(route('widget.conversation.show', conversationId));
        }
    }
})

</script>

<template>
    <div>
        <div v-if="open">
            <div v-for="message in messages" :key="message.id">
                {{ message.content }}
            </div>
            <div class="flex justify-start" v-if="loading">
                <div class="flex-none bg-gray-200 rounded shadow p-3">
                    <div class="flex space-x-1">
                        <span class="dot"></span>
                        <span class="dot"></span>
                        <span class="dot"></span>
                    </div>
                </div>
            </div>
            <form @submit.prevent="submitForm">
                <textarea v-model="form.prompt" class="w-full h-24"></textarea>
                <button class="flex-none" type="submit">Send</button>
            </form>
        </div>
        <div class="flex justify-end">
            <button @click="open = !open">Open</button>
        </div>
    </div>
</template>

<style scoped>
    .dot {
        @apply w-2 h-2 rounded-full bg-gray-400;
        animation: 1.2s typing-dot ease-in-out infinite;
    }
    .dot:nth-of-type(2) {
        animation-delay: 0.15s;
    }
    .dot:nth-of-type(3) {
        animation-delay: 0.25s;
    }
    @keyframes typing-dot {
        15% {
            transform: translateY(-35%);
            opacity: 0.5;
        }
        30% {
            transform: translateY(0%);
            opacity: 1;
        }
    }

</style>
