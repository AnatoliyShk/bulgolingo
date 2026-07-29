<script setup>
import { computed, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
    exercises:     Array,
    exerciseTypes: Array,
    lessons:       Array,
});

const selectedLesson = ref('');
const filterLesson   = ref('');

const filteredExercises = computed(() =>
    filterLesson.value
        ? props.exercises.filter(e => e.lesson?.id === filterLesson.value)
        : props.exercises
);

function typeLabel(value) {
    return props.exerciseTypes.find(t => t.value === value)?.label ?? value;
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
            <div class="flex items-center justify-between gap-3">
                <Breadcrumb :items="[
                    { label: 'Admin', href: route('admin.index') },
                    { label: 'Exercises' },
                ]" />
                <div class="flex items-center gap-2">
                    <select
                        v-model="selectedLesson"
                        class="rounded border border-gray-300 px-3 py-1.5 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-400 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
                    >
                        <option value="">Select lesson…</option>
                        <option v-for="lesson in lessons" :key="lesson.id" :value="lesson.id">{{ lesson.name }}</option>
                    </select>
                    <Link
                        :href="selectedLesson ? route('admin.exercises.create', selectedLesson) : '#'"
                        :class="selectedLesson
                            ? 'rounded bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700'
                            : 'rounded bg-blue-300 px-4 py-2 text-sm text-white cursor-not-allowed dark:bg-blue-900'"
                        @click.prevent="selectedLesson && router.visit(route('admin.exercises.create', selectedLesson))"
                    >+ New Exercise</Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-4">

                <!-- Filter bar -->
                <div class="flex items-center gap-3 rounded-lg border border-gray-200 bg-white px-4 py-3 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Filter by lesson</span>
                    <select
                        v-model="filterLesson"
                        class="w-64 rounded border border-gray-300 px-3 py-1.5 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-400 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200"
                    >
                        <option value="">All lessons</option>
                        <option v-for="lesson in lessons" :key="lesson.id" :value="lesson.id">{{ lesson.name }}</option>
                    </select>
                    <button
                        v-if="filterLesson"
                        @click="filterLesson = ''"
                        class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                    >Clear</button>
                    <span class="ml-auto text-xs text-gray-400 dark:text-gray-500">
                        {{ filteredExercises.length }} / {{ exercises.length }} exercises
                    </span>
                </div>

                <div v-if="filteredExercises.length === 0" class="text-gray-500 dark:text-gray-400">
                    {{ exercises.length === 0 ? 'No exercises yet.' : 'No exercises match the selected lesson.' }}
                </div>

                <div v-else class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow dark:border-gray-700 dark:bg-gray-800">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Type</th>
                                <th v-if="!filterLesson" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Lesson</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Created</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="exercise in filteredExercises" :key="exercise.id">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">{{ exercise.name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ typeLabel(exercise.decision_type) }}</td>
                                <td v-if="!filterLesson" class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    <Link
                                        v-if="exercise.lesson"
                                        :href="route('admin.lessons.edit', exercise.lesson.id)"
                                        class="text-blue-600 hover:underline dark:text-blue-400"
                                    >{{ exercise.lesson.name }}</Link>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ new Date(exercise.created_at).toLocaleDateString() }}</td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link
                                            :href="route('admin.exercises.edit', exercise.id)"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-md text-blue-600 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-900/30 transition-colors"
                                            title="Edit"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </Link>
                                        <button
                                            @click="deleteExercise(exercise.id)"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-md text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/30 transition-colors"
                                            title="Delete"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
