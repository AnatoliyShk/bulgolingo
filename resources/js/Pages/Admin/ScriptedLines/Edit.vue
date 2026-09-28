<script setup>
import '@/assets/scss/components/admin/scripted-lines.scss';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import ScriptedLineForm from '@/Components/Forms/ScriptedLineForm.vue';

defineProps({
    line: Object,
    dialogues: Array,
});
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb :items="[
                { label: 'Admin', href: route('admin.index') },
                { label: 'Scripted Lines', href: route('admin.scripted-lines.index') },
                { label: `Line #${line.id}` },
            ]" />
        </template>

        <div class="admin-page__body">
            <div class="admin-page__container--narrow">
                <section class="admin-card">
                    <h3 class="admin-card__title">Line Details</h3>
                    <ScriptedLineForm :line="line" :dialogues="dialogues" />
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
