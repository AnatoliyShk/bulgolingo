<script setup>
import '@/assets/scss/components/admin/exercises.scss';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import ExerciseForm from '@/Components/Forms/ExerciseForm.vue';

defineProps({
    lesson: Object,
    exerciseTypes: Array,
});
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb :items="[
                { label: 'Admin', href: route('admin.index') },
                { label: 'Lessons', href: route('admin.lessons.index') },
                { label: lesson.name, href: route('admin.lessons.edit', lesson.id) },
                { label: 'Create Exercise' },
            ]" />
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
                <section class="admin-card">
                    <ExerciseForm
                        :exercise-types="exerciseTypes"
                        :lesson-id="lesson.id"
                        :submit-route="route('admin.exercises.store', lesson.id)"
                        :cancel-href="route('admin.lessons.edit', lesson.id)"
                    />
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
