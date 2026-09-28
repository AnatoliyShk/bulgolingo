<script setup>
import '@/assets/scss/components/admin/scripted-lines.scss';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import ScriptedLineForm from '@/Components/Forms/ScriptedLineForm.vue';

defineProps({
    dialogues: Array,
    selectedDialogueId: { type: Number, default: null },
});
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb :items="[
                { label: 'Admin', href: route('admin.index') },
                { label: 'Scripted Lines', href: route('admin.scripted-lines.index') },
                { label: 'Create' },
            ]" />
        </template>

        <div class="admin-page__body">
            <div class="admin-page__container--narrow">
                <section class="admin-card">
                    <h3 class="admin-card__title">Line Details</h3>
                    <ScriptedLineForm :dialogues="dialogues" :selected-dialogue-id="selectedDialogueId" />
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
