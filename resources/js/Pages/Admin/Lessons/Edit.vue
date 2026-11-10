<script setup>
import '@/assets/scss/components/admin/lessons.scss';
import { Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import LessonForm from '@/Components/Forms/LessonForm.vue';
import { confirmVisit } from '@/confirmVisit';

defineProps({
    lesson: Object,
    exerciseTypes: Array,
});

function deleteExercise(exerciseId) {
    confirmVisit('Delete this exercise?', route('admin.exercises.destroy', exerciseId));
}
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb :items="[
                { label: 'Admin', href: route('admin.index') },
                { label: 'Lessons', href: route('admin.lessons.index') },
                { label: lesson.name },
            ]" />
        </template>

        <div class="admin-page__body">
            <div class="admin-page__container--medium">

                <!-- ── Lesson details ───────────────────────────────── -->
                <section class="admin-card">
                    <h3 class="admin-card__title">Lesson Details</h3>
                    <LessonForm :lesson="lesson" />
                </section>

                <!-- ── Exercises ───────────────────────────────────── -->
                <section class="admin-card">
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
