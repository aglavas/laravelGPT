<script setup>
import { computed, ref, watch, onMounted, nextTick } from 'vue';
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

const toggleOpen = () => {
    open.value = !open.value;

    window.parent.postMessage({
        action: 'chatbot_toggle',
        open: open.value
    }, '*'); //good practice for security is to set the origin

    scrollToBottom();
}

const setupEcho = () => {
    if (!props.conversation) return;
    console.log(props.conversation);
    window.Echo.channel("conversations."+ props.conversation.id + ".messages")
        .stopListening('.PromptResponseStarted')
        .stopListening('.PromptResponseUpdated')
        .listen('.PromptResponseStarted', (event) => {
            console.log(event);
            loading.value = true;
            scrollToBottom();
        })
        .listen('.PromptResponseUpdated', (event) => {
            console.log(event);
            messages.value.push({
                id: event.id,
                content: event.content,
                role: event.role
            });
            loading.value = false;
            scrollToBottom();
        });
}

const scrollToBottom = () => {
    nextTick(() => {
        const messages = document.querySelector('.messages');
        messages.scrollTop = messages.scrollHeight;
    });
}

watch(() => props.conversation, () => {
    setupEcho();
    if (!props.conversation) return;
    localStorage.setItem('conversation_id', props.conversation.id);
    scrollToBottom();
})

onMounted(() => {
    setupEcho();
    if (!props.conversation) {
        const conversationId = localStorage.getItem('conversation_id');
        if (conversationId) {
            router.visit(route('widget.conversation.show', conversationId));
        }
    }

    scrollToBottom();
})

</script>

<template>
    <div class="min-h-screen relative">
        <transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="transform opacity-0 scale-95"
            enter-to-class="transform opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="transform opacity-100 scale-100"
            leave-to-class="transform opacity-0 scale-95"
        >
            <div v-show="open" class="bg-white border-2 border-gray-100 shadow">

            </div>
            <div v-if="open" class="bg-white border-2 border-gray-100 shadow-sm rounded-lg">
                <div class="px-8 py-3 space-y-4 h-[280px] overflow-y-scroll">
                    <div v-for="message in messages" :key="message.id">
                        <div class="flex" :class="[message.role === 'user' ? 'justify-end' : 'justify-start']">
                            <div class="w-4/5 rounded shadow p-3" :class="[message.role === 'user' ? 'bg-blue-400 text-white' : 'bg-gray-100 text-gray-800']">
                                {{ message.content }}
                            </div>
                        </div>
                    </div>
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
                    <div class="flex bg-white pl-2 py-1 rounded-bg-lg">
                        <textarea placeholder="What would you like to know?" v-model="form.prompt" class="w-full h-16 border border-gray-200 placeholder-gray-400 rounded-md resize-none "></textarea>
                        <button class="flex-none" type="submit">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                            </svg>
                        </button>
                    </div>
                </form>
            </div>
        </transition>
        <div class="absolute inset-x-1 bottom-1 flex justify-end">
            <button @click="toggleOpen" class="rounded-full p-3 hover:scale-105 hover:-rotate-12 ease-in-out duration-300 transition-transform bg-gray-800 text-white">
                <svg v-if="!open" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 01-.825-.242m9.345-8.334a2.126 2.126 0 00-.476-.095 48.64 48.64 0 00-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0011.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" />
                </svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

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
