<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import RequestMetricsPanel from '@/Components/Admin/RequestMetricsPanel.vue';

// Every prop but the title belongs to the panel, which owns their defaults.
defineOptions({ inheritAttrs: false });

defineProps({
    title: { type: String, required: true },
});
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb :items="[
                { label: 'Admin', href: route('admin.index') },
                { label: title },
            ]" />
        </template>

        <RequestMetricsPanel v-bind="$attrs" />
    </AuthenticatedLayout>
</template>
