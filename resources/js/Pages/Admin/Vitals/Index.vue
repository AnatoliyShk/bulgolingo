<script setup>
import '@/assets/scss/components/admin/vitals.scss';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
    metrics: {
        type: Array,
        default: () => [],
    },
});

const formatValue = (metric) => {
    if (metric.p75 === null) {
        return '—';
    }

    return metric.unit === 'score' ? metric.p75.toFixed(3) : `${Math.round(metric.p75)}ms`;
};

const ratingPercent = (metric, key) => {
    if (metric.count === 0) {
        return 0;
    }

    return (metric.ratings[key] / metric.count) * 100;
};
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb :items="[
                { label: 'Admin', href: route('admin.index') },
                { label: 'Web Vitals' },
            ]" />
        </template>

        <div class="admin-vitals__body">
            <div class="admin-vitals__container">
                <section class="admin-vitals__grid">
                    <div v-for="metric in metrics" :key="metric.name" class="admin-vitals__panel">
                        <h2 class="admin-vitals__panel-title">{{ metric.name }}</h2>
                        <p class="admin-vitals__panel-subtitle">{{ metric.label }}</p>

                        <template v-if="metric.count > 0">
                            <div class="admin-vitals__stat">
                                <span class="admin-vitals__stat-value">{{ formatValue(metric) }}</span>
                                <span class="admin-vitals__stat-label">p75 of {{ metric.count }} samples</span>
                            </div>

                            <div class="admin-vitals__bar">
                                <div
                                    class="admin-vitals__bar-segment admin-vitals__bar-segment--good"
                                    :style="{ width: `${ratingPercent(metric, 'good')}%` }"
                                />
                                <div
                                    class="admin-vitals__bar-segment admin-vitals__bar-segment--needs-improvement"
                                    :style="{ width: `${ratingPercent(metric, 'needsImprovement')}%` }"
                                />
                                <div
                                    class="admin-vitals__bar-segment admin-vitals__bar-segment--poor"
                                    :style="{ width: `${ratingPercent(metric, 'poor')}%` }"
                                />
                            </div>

                            <ul class="admin-vitals__legend">
                                <li><span class="admin-vitals__legend-swatch admin-vitals__legend-swatch--good" />Good: {{ metric.ratings.good }}</li>
                                <li><span class="admin-vitals__legend-swatch admin-vitals__legend-swatch--needs-improvement" />Needs improvement: {{ metric.ratings.needsImprovement }}</li>
                                <li><span class="admin-vitals__legend-swatch admin-vitals__legend-swatch--poor" />Poor: {{ metric.ratings.poor }}</li>
                            </ul>
                        </template>
                        <p v-else class="admin-vitals__empty">No {{ metric.name }} samples recorded yet.</p>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
