<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import Breadcrumb from '@/Components/Breadcrumb.vue';

defineProps({
    bots: Array,
    users: Array,
});

const form = useForm({
    bot_id: '',
    user_id: '',
});

function submit() {
    form.post(route('admin.scripted-dialogues.store'));
}
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb :items="[
                { label: 'Admin', href: route('admin.index') },
                { label: 'Scripted Dialogues', href: route('admin.scripted-dialogues.index') },
                { label: 'Create' },
            ]" />
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
                <section class="rounded-lg border border-gray-200 bg-white p-6 shadow dark:border-gray-700 dark:bg-gray-800">
                    <h3 class="mb-4 text-base font-semibold text-gray-800 dark:text-gray-200">Dialogue Details</h3>

                    <form @submit.prevent="submit" class="space-y-5">
                        <div>
                            <label for="bot_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bot</label>
                            <select
                                id="bot_id"
                                v-model="form.bot_id"
                                class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-400 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                            >
                                <option value="" disabled>Select a bot</option>
                                <option v-for="bot in bots" :key="bot.id" :value="bot.id">{{ bot.name }}</option>
                            </select>
                            <p v-if="form.errors.bot_id" class="mt-1 text-xs text-red-500">{{ form.errors.bot_id }}</p>
                        </div>

                        <div>
                            <label for="user_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">User</label>
                            <select
                                id="user_id"
                                v-model="form.user_id"
                                class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-400 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                            >
                                <option value="" disabled>Select a user</option>
                                <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
                            </select>
                            <p v-if="form.errors.user_id" class="mt-1 text-xs text-red-500">{{ form.errors.user_id }}</p>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-1">
                            <Link
                                :href="route('admin.scripted-dialogues.index')"
                                class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400"
                            >Cancel</Link>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50 transition"
                            >
                                {{ form.processing ? 'Creating…' : 'Create Dialogue' }}
                            </button>
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
