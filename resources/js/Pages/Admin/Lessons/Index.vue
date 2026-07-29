<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import Breadcrumb from '@/Components/Breadcrumb.vue';

defineProps({
    lessons: Array,
});

function deleteLesson(id) {
    if (confirm('Delete this lesson?')) {
        router.delete(route('admin.lessons.destroy', id));
    }
}
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <Breadcrumb :items="[
                    { label: 'Admin', href: route('admin.index') },
                    { label: 'Lessons' },
                ]" />
                <Link
                    :href="route('admin.lessons.create')"
                    class="rounded bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700"
                >
                    + New Lesson
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div v-if="lessons.length === 0" class="text-gray-500">No lessons yet.</div>
                <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow dark:border-gray-700 dark:bg-gray-800">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Exercises</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Created</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="lesson in lessons" :key="lesson.id">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">{{ lesson.name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ lesson.exercises_count }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ new Date(lesson.created_at).toLocaleDateString() }}</td>
                            <td class="px-6 py-4 text-right text-sm">
                                <Link
                                    :href="route('admin.lessons.edit', lesson.id)"
                                    class="mr-3 text-blue-600 hover:underline dark:text-blue-400"
                                >Edit</Link>
                                <button
                                    @click="deleteLesson(lesson.id)"
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
