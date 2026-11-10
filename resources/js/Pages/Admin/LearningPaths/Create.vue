<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import LearningPathForm from '@/Components/Forms/LearningPathForm.vue';

defineProps({
    types: { type: Array, required: true },
    levels: { type: Array, required: true },
});
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb :items="[
                { label: 'Admin', href: route('admin.index') },
                { label: 'Learning Paths', href: route('admin.learning-paths.index') },
                { label: 'Create' },
            ]" />
        </template>

        <div class="admin-page__body">
            <div class="admin-page__container--narrow">
                <section class="admin-card">
                    <h3 class="admin-card__title">Path Details</h3>
                    <LearningPathForm :types="types" :levels="levels" />
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
