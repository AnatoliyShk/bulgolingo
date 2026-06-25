<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import Breadcrumb from '@/Components/Breadcrumb.vue';

defineProps({
    bots: Array,
});

function deleteBot(id) {
    if (confirm('Delete this bot?')) {
        router.delete(route('admin.bots.destroy', id));
    }
}
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <Breadcrumb :items="[
                    { label: 'Admin', href: route('admin.index') },
                    { label: 'Bots' },
                ]" />
                <Link
                    :href="route('admin.bots.create')"
                    class="rounded bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700"
                >
                    + New Bot
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div v-if="bots.length === 0" class="text-gray-500">No bots yet.</div>
                <div v-else class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow dark:border-gray-700 dark:bg-gray-800">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Description</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Dialogues</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Created</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="bot in bots" :key="bot.id">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">{{ bot.name }}</td>
                                <td class="max-w-xs px-6 py-4 text-sm text-gray-500 dark:text-gray-400 truncate">{{ bot.description }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ bot.scripted_dialogues_count }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ new Date(bot.created_at).toLocaleDateString() }}</td>
                                <td class="px-6 py-4 text-right text-sm">
                                    <Link
                                        :href="route('admin.bots.edit', bot.id)"
                                        class="mr-3 text-blue-600 hover:underline dark:text-blue-400"
                                    >Edit</Link>
                                    <button
                                        @click="deleteBot(bot.id)"
                                        class="text-red-600 hover:underline dark:text-red-400"
                                    >Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
