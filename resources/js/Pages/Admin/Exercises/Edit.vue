<script setup>
import '@/assets/scss/components/admin/exercises.scss';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import ExerciseForm from '@/Components/Forms/ExerciseForm.vue';

import { computed } from 'vue';

const props = defineProps({
    exercise: Object,
    exerciseTypes: Array,
});

const lesson = computed(() => props.exercise.lessons?.[0] ?? null);
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb :items="[
                { label: 'Admin', href: route('admin.index') },
                { label: 'Lessons', href: route('admin.lessons.index') },
                { label: lesson?.name, href: lesson ? route('admin.lessons.edit', lesson.id) : '#' },
                { label: 'Edit Exercise' },
            ]" />
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
                <section class="admin-card">
                    <ExerciseForm
                        :exercise="exercise"
                        :exercise-types="exerciseTypes"
                        :submit-route="route('admin.exercises.update', exercise.id)"
                        :cancel-href="lesson ? route('admin.lessons.edit', lesson.id) : route('admin.exercises.index')"
                    />
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
