<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link, router } from '@inertiajs/vue3';

const props = defineProps({
    exercises: Array,
    exerciseTypes: Array,
});

function typeLabel(value) {
    return props.exerciseTypes.find((type) => type.value === value)?.label ?? value;
}

function deleteExercise(id) {
    if (confirm('Delete this exercise?')) {
        router.delete(route('admin.exercises.destroy', id));
    }
}
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Exercises
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div v-if="exercises.length === 0" class="text-gray-500 dark:text-gray-400">No exercises yet.</div>

                <div v-else class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow dark:border-gray-700 dark:bg-gray-800">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Lesson</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Created</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="exercise in exercises" :key="exercise.id">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">{{ exercise.name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ typeLabel(exercise.decision_type) }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    <Link
                                        v-if="exercise.lesson"
                                        :href="route('admin.lessons.edit', exercise.lesson.id)"
                                        class="text-blue-600 hover:underline dark:text-blue-400"
                                    >{{ exercise.lesson.name }}</Link>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ new Date(exercise.created_at).toLocaleDateString() }}</td>
                                <td class="px-6 py-4 text-right text-sm">
                                    <Link
                                        :href="route('admin.exercises.edit', exercise.id)"
                                        class="mr-3 text-blue-600 hover:underline dark:text-blue-400"
                                    >Edit</Link>
                                    <button
                                        @click="deleteExercise(exercise.id)"
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
