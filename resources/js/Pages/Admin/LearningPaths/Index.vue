<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link, router } from '@inertiajs/vue3';

defineProps({
    learningPaths: Array,
});

function deletePath(id) {
    if (confirm('Delete this learning path?')) {
        router.delete(route('admin.learning-paths.destroy', id));
    }
}
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Learning Paths
                </h2>
                <Link
                    :href="route('admin.learning-paths.create')"
                    class="rounded bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700"
                >
                    + New Path
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div v-if="learningPaths.length === 0" class="text-gray-500 dark:text-gray-400">No learning paths yet.</div>

                <div v-else class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow dark:border-gray-700 dark:bg-gray-800">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Language</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Lessons</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Created</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="path in learningPaths" :key="path.id">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">{{ path.name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ path.language }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ path.lessons_count }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ new Date(path.created_at).toLocaleDateString() }}</td>
                                <td class="px-6 py-4 text-right text-sm">
                                    <Link
                                        :href="route('admin.learning-paths.edit', path.id)"
                                        class="mr-3 text-blue-600 hover:underline dark:text-blue-400"
                                    >Edit</Link>
                                    <button
                                        @click="deletePath(path.id)"
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
