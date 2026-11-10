<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import ScriptedDialogueForm from '@/Components/Forms/ScriptedDialogueForm.vue';

defineProps({
    bots: Array,
    users: Array,
});
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb :items="[
                { label: 'Admin', href: route('admin.index') },
                { label: 'Scripted Dialogues', href: route('admin.scripted-dialogues.index') },
                { label: 'Create' },
            ]" />
        </template>

        <div class="admin-page__body">
            <div class="admin-page__container--narrow">
                <section class="admin-card">
                    <h3 class="admin-card__title">Dialogue Details</h3>
                    <ScriptedDialogueForm :bots="bots" :users="users" />
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
