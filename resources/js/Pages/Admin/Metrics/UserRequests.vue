<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import RequestMetricsPanel from '@/Components/Admin/RequestMetricsPanel.vue';

defineProps({
    buckets: {
        type: Array,
        default: () => [],
    },
    totalRequests: {
        type: Number,
        default: 0,
    },
    p95DurationMs: {
        type: Number,
        default: 0,
    },
    queues: {
        type: Array,
        default: () => [],
    },
    slowRequests: {
        type: Array,
        default: () => [],
    },
    cacheHits: {
        type: Number,
        default: 0,
    },
    cacheMisses: {
        type: Number,
        default: 0,
    },
    cacheHitRate: {
        type: Number,
        default: null,
    },
});
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb :items="[
                { label: 'Admin', href: route('admin.index') },
                { label: 'User Request Metrics' },
            ]" />
        </template>

        <RequestMetricsPanel
            :buckets="buckets"
            :total-requests="totalRequests"
            :p95-duration-ms="p95DurationMs"
            :queues="queues"
            :slow-requests="slowRequests"
            :cache-hits="cacheHits"
            :cache-misses="cacheMisses"
            :cache-hit-rate="cacheHitRate"
        />
    </AuthenticatedLayout>
</template>
