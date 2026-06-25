<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link, useForm, router } from '@inertiajs/vue3';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
    dialogue: Object,
    bots: Array,
    users: Array,
});

const form = useForm({
    bot_id: props.dialogue.bot_id,
    user_id: props.dialogue.user_id,
});

function submit() {
    form.patch(route('admin.scripted-dialogues.update', props.dialogue.id));
}

function deleteLine(id) {
    if (confirm('Delete this line?')) {
        router.delete(route('admin.scripted-lines.destroy', id));
    }
}
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb :items="[
                { label: 'Admin', href: route('admin.index') },
                { label: 'Scripted Dialogues', href: route('admin.scripted-dialogues.index') },
                { label: `Dialogue #${dialogue.id}` },
            ]" />
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl space-y-8 px-4 sm:px-6 lg:px-8">

                <!-- Dialogue settings -->
                <section class="rounded-lg border border-gray-200 bg-white p-6 shadow dark:border-gray-700 dark:bg-gray-800">
                    <h3 class="mb-4 text-base font-semibold text-gray-800 dark:text-gray-200">Dialogue Details</h3>

                    <form @submit.prevent="submit" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bot</label>
                            <select
                                v-model="form.bot_id"
                                class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-400 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                            >
                                <option v-for="bot in bots" :key="bot.id" :value="bot.id">{{ bot.name }}</option>
                            </select>
                            <p v-if="form.errors.bot_id" class="mt-1 text-xs text-red-500">{{ form.errors.bot_id }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">User</label>
                            <select
                                v-model="form.user_id"
                                class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-400 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                            >
                                <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
                            </select>
                            <p v-if="form.errors.user_id" class="mt-1 text-xs text-red-500">{{ form.errors.user_id }}</p>
                        </div>

                        <div class="flex justify-end">
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                            >
                                {{ form.processing ? 'Saving…' : 'Save' }}
                            </button>
                        </div>
                    </form>
                </section>

                <!-- Lines -->
                <section class="rounded-lg border border-gray-200 bg-white p-6 shadow dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200">
                            Lines
                            <span class="ml-2 text-xs font-normal text-gray-400">({{ dialogue.lines.length }})</span>
                        </h3>
                        <Link
                            :href="route('admin.scripted-lines.create', { scripted_dialogue_id: dialogue.id })"
                            class="text-xs font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400"
                        >
                            + Add Line
                        </Link>
                    </div>

                    <div v-if="dialogue.lines.length === 0" class="text-sm text-gray-500 dark:text-gray-400">
                        No lines yet.
                    </div>

                    <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                        <li v-for="line in dialogue.lines" :key="line.id" class="py-3">
                            <div class="flex items-start justify-between gap-4">
                                <p class="text-sm text-gray-700 dark:text-gray-300 truncate">
                                    {{ line.clause?.line_text ?? '—' }}
                                </p>
                                <div class="flex shrink-0 gap-4">
                                    <Link
                                        :href="route('admin.scripted-lines.edit', line.id)"
                                        class="text-xs text-indigo-600 hover:underline dark:text-indigo-400"
                                    >Edit</Link>
                                    <button
                                        type="button"
                                        @click="deleteLine(line.id)"
                                        class="text-xs text-red-500 hover:underline dark:text-red-400"
                                    >Delete</button>
                                </div>
                            </div>
                        </li>
                    </ul>
                </section>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
