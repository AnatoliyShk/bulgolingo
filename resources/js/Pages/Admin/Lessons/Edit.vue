<script setup>
import { useForm, router, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    lesson: Object,
    exerciseTypes: Array,
});

// ── Lesson form ────────────────────────────────────────────────────────
const lessonForm = useForm({
    name: props.lesson.name,
    description: props.lesson.description ?? '',
});

function saveLesson() {
    lessonForm.patch(route('admin.lessons.update', props.lesson.id));
}

function deleteExercise(exerciseId) {
    if (confirm('Delete this exercise?')) {
        router.delete(route('admin.exercises.destroy', exerciseId));
    }
}
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link
                    :href="route('admin.lessons.index')"
                    class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                >← Lessons</Link>
                <span class="text-gray-300 dark:text-gray-600">/</span>
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Edit Lesson
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl space-y-8 px-4 sm:px-6 lg:px-8">

                <!-- ── Lesson details ───────────────────────────────── -->
                <section class="rounded-lg border border-gray-200 bg-white p-6 shadow dark:border-gray-700 dark:bg-gray-800">
                    <h3 class="mb-4 text-base font-semibold text-gray-800 dark:text-gray-200">Lesson Details</h3>
                    <form @submit.prevent="saveLesson" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
                            <input
                                v-model="lessonForm.name"
                                type="text"
                                class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-400 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                            />
                            <p v-if="lessonForm.errors.name" class="mt-1 text-xs text-red-500">{{ lessonForm.errors.name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                            <textarea
                                v-model="lessonForm.description"
                                rows="3"
                                class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-400 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                            />
                            <p v-if="lessonForm.errors.description" class="mt-1 text-xs text-red-500">{{ lessonForm.errors.description }}</p>
                        </div>
                        <div class="flex justify-end">
                            <button
                                type="submit"
                                :disabled="lessonForm.processing"
                                class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                            >
                                {{ lessonForm.processing ? 'Saving…' : 'Save' }}
                            </button>
                        </div>
                    </form>
                </section>

                <!-- ── Exercises ───────────────────────────────────── -->
                <section class="rounded-lg border border-gray-200 bg-white p-6 shadow dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200">
                            Exercises
                            <span class="ml-2 text-xs font-normal text-gray-400">({{ lesson.exercises.length }})</span>
                        </h3>
                        <Link
                            :href="route('admin.exercises.create', lesson.id)"
                            class="text-xs font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400"
                        >
                            + Add Exercise
                        </Link>
                    </div>

                    <div v-if="lesson.exercises.length === 0" class="text-sm text-gray-500 dark:text-gray-400">
                        No exercises yet.
                    </div>

                    <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                        <li v-for="exercise in lesson.exercises" :key="exercise.id" class="py-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ exercise.name }}</p>
                                    <p class="text-xs text-gray-400">
                                        {{ exerciseTypes.find(t => t.value === exercise.decision_type)?.label ?? exercise.decision_type }}
                                    </p>
                                </div>
                                <div class="flex gap-4">
                                    <Link
                                        :href="route('admin.exercises.edit', exercise.id)"
                                        class="text-xs text-indigo-600 hover:underline dark:text-indigo-400"
                                    >Edit</Link>
                                    <button
                                        type="button"
                                        @click="deleteExercise(exercise.id)"
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
