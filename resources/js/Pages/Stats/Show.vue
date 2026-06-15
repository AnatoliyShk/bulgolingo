<script setup>
import { computed, onMounted, ref } from 'vue'
import { useTheme } from '@/composables/useTheme'
import { VueUiDonut, VueUiKpi, VueUiWordCloud } from 'vue-data-ui'
import 'vue-data-ui/style.css'

const { theme, toggleTheme } = useTheme()

const props = defineProps({
    completedLessons: {
        type: Number,
        default: 0,
    },
    completedExercises: {
        type: Number,
        default: 0,
    },
    completedLearningPaths: {
        type: Number,
        default: 0,
    },
    exercisesByType: {
        type: Array,
        default: () => [],
    },
    learnedWords: {
        type: Array,
        default: () => [],
    },
})

const isDark = computed(() => theme.value === 'dark')

// colors mirror App\Enums\ExerciseType::getDescription() labels
const typeColors = {
    'Multiple Choice': '#6366f1',
    'True/False': '#22c55e',
    'Fill in the Blank': '#f59e0b',
    'Image Matching': '#ec4899',
}

function kpiConfig(title, lightColor, darkColor) {
    return computed(() => ({
        title,
        titleFontSize: 12,
        titleColor: isDark.value ? '#6b7280' : '#9ca3af',
        titleClass: 'kpi-title',
        layoutClass: 'kpi-layout',
        valueFontSize: 36,
        valueBold: true,
        valueColor: isDark.value ? darkColor : lightColor,
        backgroundColor: 'transparent',
        useAnimation: true,
        animationFrames: 60,
    }))
}

const lessonsConfig = kpiConfig('Completed lessons', '#4f46e5', '#818cf8')
const exercisesConfig = kpiConfig('Completed exercises', '#059669', '#34d399')
const pathsConfig = kpiConfig('Completed learning paths', '#d97706', '#fbbf24')

const donutDataset = computed(() =>
    props.exercisesByType.map((stat) => ({
        name: stat.type,
        values: [stat.count],
        color: typeColors[stat.type] ?? '#9ca3af',
    }))
)

const donutConfig = computed(() => ({
    theme: isDark.value ? 'minimalDark' : 'minimal',
    responsive: true,
    useCssAnimation: true,
    startAnimation: {
        show: true,
        durationMs: 700,
    },
    style: {
        chart: {
            backgroundColor: 'transparent',
            layout: {
                labels: {
                    dataLabels: {
                        show: false,
                    },
                    hollow: {
                        show: true,
                        total: {
                            show: true,
                            text: 'exercises',
                        },
                    },
                },
                donut: {
                    strokeWidth: 48,
                    borderWidth: 2,
                    borderColorAuto: true,
                },
            },
            legend: {
                show: true,
                backgroundColor: 'transparent',
                position: 'bottom',
                showValue: false,
                showPercentage: true,
            },
            tooltip: {
                show: true,
            },
            title: {
                show: false,
            },
        },
    },
}))

const wordCloudDataset = computed(() =>
    props.learnedWords.map((item) => ({
        name: item.word,
        value: item.count,
    }))
)

const wordCloudConfig = computed(() => ({
    theme: isDark.value ? 'celebrationNight' : 'celebration',
    responsive: true,
    useCssAnimation: true,
    style: {
        chart: {
            backgroundColor: 'transparent',
            width: 512,
            height: 320,
            controls: {
                backgroundColor: 'transparent',
                buttonColor: 'transparent',
            },
            title: {
                show: false,
            },
        },
    },
}))

const ready = ref(false)

onMounted(() => {
    requestAnimationFrame(() => {
        ready.value = true
    })
})
</script>

<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 flex flex-col items-center p-6 pt-16">
        <button
            @click="toggleTheme"
            class="fixed top-4 right-4 z-50 rounded-md border border-gray-200 bg-white px-2 py-1 text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400"
            :title="theme === 'dark' ? 'Switch to light' : 'Switch to dark'"
        >{{ theme === 'dark' ? '☀️' : '🌙' }}</button>

        <h1 class="enter-item text-xl font-semibold text-gray-900 dark:text-gray-100 text-center mb-8" :class="{ 'is-visible': ready }">Your Stats</h1>

        <div class="stats-row">
            <div class="stat-card enter-item" :class="{ 'is-visible': ready }" style="transition-delay: 0.1s">
                <VueUiKpi :dataset="completedLessons" :config="lessonsConfig" />
            </div>

            <div class="stat-card enter-item" :class="{ 'is-visible': ready }" style="transition-delay: 0.18s">
                <VueUiKpi :dataset="completedExercises" :config="exercisesConfig" />
            </div>

            <div class="stat-card enter-item" :class="{ 'is-visible': ready }" style="transition-delay: 0.26s">
                <VueUiKpi :dataset="completedLearningPaths" :config="pathsConfig" />
            </div>
        </div>

        <div class="stat-card donut-card enter-item" :class="{ 'is-visible': ready }" style="transition-delay: 0.34s">
            <p class="text-xs uppercase tracking-wide text-gray-400 dark:text-gray-500 text-center mb-4">Exercises by type</p>

            <VueUiDonut v-if="donutDataset.length" :dataset="donutDataset" :config="donutConfig" />
            <p v-else class="text-sm text-gray-400 dark:text-gray-500 text-center py-8">No completed exercises yet</p>
        </div>

        <div class="stat-card donut-card enter-item" :class="{ 'is-visible': ready }" style="transition-delay: 0.42s">
            <p class="text-xs uppercase tracking-wide text-gray-400 dark:text-gray-500 text-center mb-4">Words you've learned</p>

            <VueUiWordCloud v-if="wordCloudDataset.length" :dataset="wordCloudDataset" :config="wordCloudConfig" />
            <p v-else class="text-sm text-gray-400 dark:text-gray-500 text-center py-8">No learned words yet</p>
        </div>
    </div>
</template>

<style scoped>
.enter-item {
    opacity: 0;
    transform: translateY(1rem);
    transition: opacity 0.5s ease, transform 0.5s ease;
}

.enter-item.is-visible {
    opacity: 1;
    transform: translateY(0);
}

.stats-row {
    width: 100%;
    max-width: 40rem;
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.25rem;
    margin-bottom: 1.25rem;
}

@media (min-width: 640px) {
    .stats-row {
        grid-template-columns: repeat(3, 1fr);
    }
}

.stat-card {
    background: #ffffff;
    border-radius: 0.75rem;
    border: 1px solid #f3f4f6;
    box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
    padding: 1.5rem;
}

.donut-card {
    width: 100%;
    max-width: 40rem;
}

:deep(.kpi-layout) {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}

:deep(.kpi-title) {
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
</style>

<style>
html.dark .stat-card {
    background: #1f2937;
    border-color: #374151;
}
</style>
