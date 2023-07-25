<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import TextInput from "@/Components/TextInput.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import InputLabel from "@/Components/InputLabel.vue";
import InputError from "@/Components/InputError.vue";

const props = defineProps({
    url: {
        type: String,
    },
    conversations: {
        type: Array
    },
    urls : {
        type: Array
    }
});

const form = useForm({
    url: props.url,
    type: 'URL'
});

const submit = () => {
    form.post(route('context.create'), {
        onSuccess: () => form.reset('url'),
    });
};

</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Dashboard</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">

                        <div class="mt-12">
                            <div class="font-bold">Conversations</div>
                            <div v-for="conversation in conversations">
                                <a :href="route('dashboard.conversation.show', conversation.id)">{{ conversation.id }}</a>
                            </div>
                        </div>

                        <form @submit.prevent="submit">
                            <div>
                                <InputLabel for="url" value="Url" />

                                <TextInput
                                    id="url"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.url"
                                    required
                                    autofocus
                                    autocomplete="URL"
                                />

                                <InputError class="mt-2" :message="form.errors.url" />
                            </div>

                            <div class="flex items-center justify-end mt-4">
                                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                    Save
                                </PrimaryButton>
                            </div>
                        </form>

                        <div class="mt-12">
                            <div v-for="url in urls">
                                {{ url }}
                            </div>
                        </div>


                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
