<script setup>
import '@/assets/scss/components/stats/show.scss'
import { computed, onMounted, ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import { useTheme } from '@/composables/useTheme'
import { VueUiWordCloud, VueUiDonutEvolution } from 'vue-data-ui'
import 'vue-data-ui/style.css'

const { theme, toggleTheme } = useTheme()

const props = defineProps({
    completedExercises: {
        type: Number,
        default: 0,
    },
    completedLessons: {
        type: Number,
        default: 0,
    },
    completedLearningPaths: {
        type: Number,
        default: 0,
    },
    learnedWords: {
        type: Array,
        default: () => [],
    },
    activityByType: {
        type: Array,
        default: () => [],
    },
    activityDays: {
        type: Array,
        default: () => [],
    },
    appName: {
        type: String,
        default: 'Balkanbuddy',
    },
})

const isDark = computed(() => theme.value === 'dark')

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

const activityConfig = computed(() => ({
    style: {
        fontFamily: "'PT Sans', sans-serif",
        chart: {
            backgroundColor: 'transparent',
            color: isDark.value ? '#f3e9d8' : '#2b231b',
            layout: {
                grid: {
                    stroke: isDark.value ? 'rgba(243,233,216,0.08)' : 'rgba(43,35,27,0.1)',
                    xAxis: {
                        dataLabels: {
                            show: true,
                            values: props.activityDays,
                            fontSize: 13,
                            color: isDark.value ? '#f3e9d8' : '#2b231b',
                        },
                    },
                    yAxis: {
                        dataLabels: {
                            show: true,
                            fontSize: 13,
                            bold: true,
                            color: isDark.value ? '#f3e9d8' : '#2b231b',
                        },
                    },
                },
                dataLabels: {
                    show: true,
                    fontSize: 14,
                    bold: true,
                    color: isDark.value ? '#f3e9d8' : '#2b231b',
                },
            },
            legend: {
                fontSize: 14,
                bold: true,
                color: isDark.value ? '#f3e9d8' : '#2b231b',
                showValue: true,
                showPercentage: true,
                roundingPercentage: 1,
            },
        },
    },
}))

const ready = ref(false)
onMounted(() => requestAnimationFrame(() => { ready.value = true }))

const stats = computed(() => [
    { bg: 'Упражнения', en: 'completed exercises', value: props.completedExercises },
    { bg: 'Уроци',      en: 'completed lessons',   value: props.completedLessons },
    { bg: 'Пътища',     en: 'completed paths',     value: props.completedLearningPaths },
])
</script>

<template>
    <div class="pg" :class="theme">
        <div class="pg__watermark" aria-hidden="true">Ъ</div>

        <!-- Top bar -->
        <header class="bar">
            <Link :href="route('dashboard')" class="bar__back" aria-label="Back to dashboard">
                <svg class="bar__back-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </Link>
            <span class="bar__mark">{{ appName }}</span>
            <button class="bar__theme" @click="toggleTheme" :title="theme === 'dark' ? 'Switch to light' : 'Switch to dark'">
                {{ theme === 'dark' ? '☀️' : '🌙' }}
            </button>
        </header>

        <main class="sheet">
            <!-- Heading -->
            <p class="eyebrow rise" :class="{ 'rise--in': ready }">
                <span class="eyebrow__bg">Статистика</span>
                <span class="eyebrow__en">your stats</span>
            </p>

            <!-- KPI cards -->
            <div class="kpi-grid">
                <div
                    v-for="(stat, i) in stats"
                    :key="stat.en"
                    class="kpi rise"
                    :class="{ 'rise--in': ready }"
                    :style="{ transitionDelay: (i * 0.08) + 's' }"
                >
                    <span class="kpi__value">{{ stat.value }}</span>
                    <span class="kpi__bg">{{ stat.bg }}</span>
                    <span class="kpi__en">{{ stat.en }}</span>
                </div>
            </div>

            <!-- Seam -->
            <div class="seam rise" :class="{ 'rise--in': ready }" style="transition-delay:.28s" aria-hidden="true"></div>

            <!-- Word cloud -->
            <section class="rise" :class="{ 'rise--in': ready }" style="transition-delay:.36s">
                <p class="eyebrow">
                    <span class="eyebrow__bg">Думи</span>
                    <span class="eyebrow__en">words you've learned</span>
                </p>

                <div class="kpi rise" :class="{ 'rise--in': ready }" style="transition-delay:.3s; margin-bottom:1rem;">
                    <span class="kpi__value">{{ learnedWords.length }}</span>
                    <span class="kpi__bg">Уникални думи</span>
                    <span class="kpi__en">unique words learned</span>
                </div>

                <div class="cloud-wrap">
                    <VueUiWordCloud v-if="wordCloudDataset.length" :dataset="wordCloudDataset" :config="wordCloudConfig" />
                    <p v-else class="empty">No learned words yet.</p>
                </div>
            </section>

            <!-- Seam -->
            <div class="seam rise" :class="{ 'rise--in': ready }" style="transition-delay:.44s" aria-hidden="true"></div>

            <!-- Activity -->
            <section class="rise" :class="{ 'rise--in': ready }" style="transition-delay:.52s">
                <p class="eyebrow">
                    <span class="eyebrow__bg">Активност</span>
                    <span class="eyebrow__en">activity</span>
                </p>

                <div class="activity-wrap">
                    <VueUiDonutEvolution
                        v-if="activityByType.length"
                        :dataset="activityByType"
                        :config="activityConfig"
                    />
                    <p v-else class="empty">No activity yet.</p>
                </div>
            </section>
        </main>
    </div>
</template>

