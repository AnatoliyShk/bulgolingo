<script setup>
import '@/assets/scss/components/admin/metrics.scss';
import { useTheme } from '@/composables/useTheme';
import { computed, ref } from 'vue';
import { VueUiXy } from 'vue-data-ui';
import 'vue-data-ui/style.css';

const { theme } = useTheme();

const props = defineProps({
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

const expandedRoutes = ref(new Set());

const toggleGroup = (route) => {
    if (expandedRoutes.value.has(route)) {
        expandedRoutes.value.delete(route);
    } else {
        expandedRoutes.value.add(route);
    }
};

const today = new Date().toISOString().slice(0, 10);
const lastSeenDate = ref('');

// occurredAt is "YYYY-MM-DD HH:MM:SS", so a plain prefix match against the
// <input type="date"> value (YYYY-MM-DD) is enough — no date parsing needed.
const filteredSlowRequests = computed(() => {
    if (!lastSeenDate.value) {
        return props.slowRequests;
    }

    return props.slowRequests.filter((entry) => entry.occurredAt.startsWith(lastSeenDate.value));
});

const slowRequestGroups = computed(() => {
    const groups = new Map();

    for (const entry of filteredSlowRequests.value) {
        if (!groups.has(entry.route)) {
            groups.set(entry.route, []);
        }
        groups.get(entry.route).push(entry);
    }

    // slowRequests arrives newest-first, so each group's first entry is its most recent occurrence.
    return Array.from(groups, ([route, entries]) => ({
        route,
        entries,
        count: entries.length,
        maxDurationMs: Math.max(...entries.map((entry) => entry.durationMs)),
        lastSeenAt: entries[0].occurredAt,
    })).sort((a, b) => b.count - a.count);
});

const isDark = computed(() => theme.value === 'dark');
const ink = computed(() => (isDark.value ? '#e5e7eb' : '#1f2937'));

const chartDataset = computed(() => [
    {
        name: 'Requests',
        type: 'bar',
        series: props.buckets.map((bucket) => bucket.value),
        color: '#3a6fe0',
    },
]);

const chartConfig = computed(() => ({
    chart: {
        fontFamily: 'inherit',
        backgroundColor: 'transparent',
        color: ink.value,
        title: {
            text: 'Requests by duration bucket',
            show: false,
        },
        legend: {
            show: false,
        },
        tooltip: {
            show: true,
        },
        zoom: {
            show: false,
        },
        grid: {
            labels: {
                color: ink.value,
                xAxisLabels: {
                    values: props.buckets.map((bucket) => bucket.name),
                    fontSize: 10,
                    color: ink.value,
                },
            },
        },
    },
    bar: {
        labels: {
            show: true,
            color: ink.value,
            formatter: ({ value }) => {
                const pct = props.totalRequests > 0 ? Math.round((value / props.totalRequests) * 100) : 0;

                return `${value} (${pct}%)`;
            },
        },
    },
}));
</script>

<template>
    <div class="admin-metrics__body">
        <div class="admin-metrics__container">
            <section class="admin-metrics__kpis">
                <div class="admin-metrics__kpi">
                    <div class="admin-metrics__kpi-value">{{ totalRequests }}</div>
                    <div class="admin-metrics__kpi-label">Total requests recorded</div>
                </div>
                <div class="admin-metrics__kpi">
                    <div class="admin-metrics__kpi-value">{{ p95DurationMs }}ms</div>
                    <div class="admin-metrics__kpi-label">P95 request duration</div>
                </div>
                <div class="admin-metrics__kpi">
                    <div class="admin-metrics__kpi-value">{{ cacheHitRate !== null ? `${cacheHitRate}%` : '—' }}</div>
                    <div class="admin-metrics__kpi-label">Cache hit rate</div>
                    <div class="admin-metrics__kpi-sub">{{ cacheHits }} hits · {{ cacheMisses }} misses</div>
                </div>
            </section>

            <section class="admin-metrics__panel">
                <h2 class="admin-metrics__panel-title">Requests exceeding p95</h2>

                <div class="admin-metrics__slow-filter">
                    <label for="slow-requests-date" class="admin-metrics__slow-filter-label">Last seen on</label>
                    <input
                        id="slow-requests-date"
                        v-model="lastSeenDate"
                        type="date"
                        :max="today"
                        class="admin-metrics__slow-filter-input"
                    />
                    <button
                        v-if="lastSeenDate"
                        type="button"
                        class="admin-metrics__slow-filter-clear"
                        @click="lastSeenDate = ''"
                    >
                        Clear
                    </button>
                </div>

                <table v-if="slowRequestGroups.length" class="admin-metrics__slow-table">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Route</th>
                            <th>Occurrences</th>
                            <th>Worst duration</th>
                            <th>Last seen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-for="group in slowRequestGroups" :key="group.route">
                            <tr
                                class="admin-metrics__slow-group-row"
                                role="button"
                                tabindex="0"
                                :aria-expanded="expandedRoutes.has(group.route)"
                                @click="toggleGroup(group.route)"
                                @keydown.enter="toggleGroup(group.route)"
                                @keydown.space.prevent="toggleGroup(group.route)"
                            >
                                <td>
                                    <svg
                                        class="admin-metrics__slow-chevron"
                                        :class="{ 'admin-metrics__slow-chevron--open': expandedRoutes.has(group.route) }"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                    </svg>
                                </td>
                                <td>{{ group.route }}</td>
                                <td>{{ group.count }}</td>
                                <td>{{ group.maxDurationMs }}ms</td>
                                <td>{{ group.lastSeenAt }}</td>
                            </tr>
                            <tr v-if="expandedRoutes.has(group.route)" class="admin-metrics__slow-detail-row">
                                <td colspan="5">
                                    <table class="admin-metrics__slow-detail-table">
                                        <thead>
                                            <tr>
                                                <th>Method</th>
                                                <th>Status</th>
                                                <th>Duration</th>
                                                <th>Queries</th>
                                                <th>Memory</th>
                                                <th>p95 at time</th>
                                                <th>When</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(entry, index) in group.entries" :key="index">
                                                <td>{{ entry.method }}</td>
                                                <td>{{ entry.status }}</td>
                                                <td>{{ entry.durationMs }}ms</td>
                                                <td>{{ entry.queries }}</td>
                                                <td>{{ entry.memoryMb }}MB</td>
                                                <td>{{ entry.p95Ms }}ms</td>
                                                <td>{{ entry.occurredAt }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
                <p v-else-if="lastSeenDate" class="admin-metrics__empty">No requests exceeded p95 on this date.</p>
                <p v-else class="admin-metrics__empty">No requests have exceeded the p95 threshold yet.</p>
            </section>

            <section class="admin-metrics__panel">
                <h2 class="admin-metrics__panel-title">Request duration histogram</h2>

                <VueUiXy
                    v-if="buckets.length"
                    :dataset="chartDataset"
                    :config="chartConfig"
                />
                <p v-else class="admin-metrics__empty">No request metrics recorded yet.</p>
            </section>

            <section class="admin-metrics__panel">
                <h2 class="admin-metrics__panel-title">Queue stats</h2>

                <table v-if="queues.length" class="admin-metrics__queue-table">
                    <thead>
                        <tr>
                            <th>Queue</th>
                            <th>Pending jobs</th>
                            <th>Oldest job age</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="queue in queues" :key="queue.name">
                            <td>{{ queue.name }}</td>
                            <td>{{ queue.depth }}</td>
                            <td>{{ queue.oldestWaitSeconds }}s</td>
                        </tr>
                    </tbody>
                </table>
                <p v-else class="admin-metrics__empty">No queue metrics recorded yet.</p>
            </section>
        </div>
    </div>
</template>
