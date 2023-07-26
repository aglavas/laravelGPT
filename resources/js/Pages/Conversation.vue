<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    conversation: {
        type: Object
    }
});

const form = useForm({
    messages: props.conversation.messages
})

const submitForm = () => {
    form.post(route('dashboard.conversation.update', props.conversation.id), {
        preserveScroll: true
    });
};

const togglePartner = (clickedMessage, clickedIndex) => {
    let partnerIndex = clickedMessage.role === 'user' ? clickedIndex + 1 : clickedIndex - 1;
    let partnerMessage = form.messages[partnerIndex];

    if (partnerMessage && partnerMessage.role !== clickedMessage.role) {
        let newUsable = !form.messages[clickedIndex].usable;
        form.messages[clickedIndex].usable = newUsable;
        form.messages[partnerIndex].usable = newUsable;
    }
}

</script>

<template>
    <Head title="Conversation" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Conversation</h2>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        {{ conversation.id }}
                        <div>
                            <div>Messages</div>
                            <form @submit.prevent="submitForm">
                                <div class="space-y-4">
                                    <div class="flex items-center space-x-2" v-for="(message, idx) in conversation.messages">
                                        <div>
                                            <input @click="togglePartner(message, idx)" type="checkbox" v-model="form.messages[idx].usable">
                                        </div>
                                        <div class="flex-1">
                                            <div>{{ message.role }}</div>
                                            <textarea class="w-full block" v-model="form.messages[idx].content"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <button type="submit">Save</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>

</style>
