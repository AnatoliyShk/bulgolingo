<script setup>
import '@/assets/scss/components/stats/show.scss'
import { computed, nextTick, onMounted, ref, watch } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import { useTheme } from '@/composables/useTheme'
import { VueUiWordCloud, VueUiDonutEvolution } from 'vue-data-ui'
import 'vue-data-ui/style.css'
import ThemeToggle from '@/Components/ThemeToggle.vue'

const { theme } = useTheme()
const mobileNavOpen = ref(false)

const page = usePage()
const appName = computed(() => page.props.appName ?? 'BalkanBuddy')

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
})

const isDark = computed(() => theme.value === 'dark')

const rootEl = ref(null)

// Exercise-type colors live only in show.scss (--activity-* custom
// properties, one per App\Enums\ExerciseType value), not in the backend
// payload. The browser already resolves the light/dark value via the
// .nb-stats.dark cascade, so we just read whatever is currently computed.
const seriesColors = ref({})

function readSeriesColors() {
    if (!rootEl.value) return

    const style = getComputedStyle(rootEl.value)

    seriesColors.value = Object.fromEntries(
        props.activityByType.map((series) => {
            const varName = `--activity-${series.type.replaceAll('_', '-')}`
            return [series.type, style.getPropertyValue(varName).trim() || '#94a3b8']
        })
    )
}

onMounted(readSeriesColors)
watch(theme, async () => {
    await nextTick()
    readSeriesColors()
})

const ink = computed(() => (isDark.value ? '#f4ead6' : '#14110b'))
const paper = computed(() => (isDark.value ? '#221d16' : '#fffaf0'))
const inkSoft = computed(() => (isDark.value ? 'rgba(244, 234, 214, 0.16)' : 'rgba(20, 17, 11, 0.16)'))

const activityDataset = computed(() =>
    props.activityByType.map((series) => ({
        ...series,
        color: seriesColors.value[series.type] ?? '#94a3b8',
    }))
)

const wordCloudDataset = computed(() =>
    props.learnedWords.map((item) => ({
        name: item.word,
        value: item.count,
    }))
)

const wordCloudConfig = computed(() => ({
    responsive: true,
    useCssAnimation: true,
    customPalette: Object.values(seriesColors.value),
    style: {
        fontFamily: "'Manrope', sans-serif",
        chart: {
            backgroundColor: 'transparent',
            color: ink.value,
            width: 512,
            height: 320,
            controls: {
                backgroundColor: 'transparent',
                buttonColor: 'transparent',
            },
            words: {
                bold: true,
                usePalette: true,
                selectedStroke: ink.value,
                maxFontSize: 46,
                minFontSize: 9,
            },
            title: {
                show: false,
            },
        },
    },
}))

const activityConfig = computed(() => ({
    style: {
        fontFamily: "'Manrope', sans-serif",
        chart: {
            backgroundColor: 'transparent',
            color: ink.value,
            layout: {
                grid: {
                    stroke: inkSoft.value,
                    xAxis: {
                        dataLabels: {
                            show: true,
                            values: props.activityDays,
                            fontSize: 13,
                            color: ink.value,
                        },
                    },
                    yAxis: {
                        dataLabels: {
                            show: true,
                            fontSize: 13,
                            bold: true,
                            color: ink.value,
                        },
                    },
                },
                line: {
                    stroke: inkSoft.value,
                },
                dataLabels: {
                    show: true,
                    fontSize: 14,
                    bold: true,
                    color: ink.value,
                },
            },
            legend: {
                backgroundColor: 'transparent',
                fontSize: 14,
                bold: true,
                color: ink.value,
                showValue: true,
                showPercentage: true,
                roundingPercentage: 1,
            },
            dialog: {
                backgroundColor: paper.value,
                color: ink.value,
                header: {
                    backgroundColor: paper.value,
                    color: ink.value,
                },
            },
        },
    },
}))

const kpis = computed(() => [
    { tag: 'Упражнения', sub: 'completed exercises', value: props.completedExercises, accent: 'cyan' },
    { tag: 'Уроци', sub: 'completed lessons', value: props.completedLessons, accent: 'green' },
    { tag: 'Пътища', sub: 'completed paths', value: props.completedLearningPaths, accent: 'orange' },
])
</script>

<template>
    <Head title="Stats">
        <link
            href="https://fonts.bunny.net/css?family=unbounded:400,600,700,800,900|manrope:400,500,600,700,800&subset=cyrillic,latin&display=swap"
            rel="stylesheet"
        />
    </Head>

    <div class="nb-stats" :class="theme" ref="rootEl">
        <header class="nb-stats__bar">
            <nav class="nb-stats__bar-inner">
                <Link :href="route('dashboard')" class="nb-stats__logo">
                    <span class="nb-stats__logo-mark" aria-hidden="true">BB</span>
                    <span class="nb-stats__logo-text">{{ appName }}</span>
                </Link>

                <div class="nb-stats__bar-group">
                    <div class="nb-stats__bar-links" :class="{ 'nb-stats__bar-links--open': mobileNavOpen }">
                        <Link :href="route('dashboard')" class="nb-stats__navlink" @click="mobileNavOpen = false">Dashboard</Link>
                    </div>

                    <div class="nb-stats__bar-actions">
                        <ThemeToggle />

                        <button
                            class="nb-stats__hamburger"
                            type="button"
                            :aria-expanded="mobileNavOpen"
                            aria-label="Toggle navigation menu"
                            @click="mobileNavOpen = !mobileNavOpen"
                        >
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path v-if="!mobileNavOpen" stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16" />
                                <path v-else stroke-linecap="round" d="M6 6l12 12M18 6L6 18" />
                            </svg>
                        </button>
                    </div>
                </div>
            </nav>
        </header>

        <main class="nb-stats__main">
            <div class="nb-stats__head nb-stats__rise">
                <span class="nb-stats__badge">Статистика</span>
                <h1 class="nb-stats__title">Your stats</h1>
                <p class="nb-stats__sub">Everything you've completed so far.</p>
            </div>

            <div class="nb-stats__kpis">
                <article
                    v-for="(kpi, i) in kpis"
                    :key="kpi.sub"
                    class="nb-stats__kpi nb-stats__rise"
                    :class="`nb-stats__kpi--${kpi.accent}`"
                    :style="{ animationDelay: (80 + i * 70) + 'ms' }"
                >
                    <span class="nb-stats__kpi-value">{{ kpi.value }}</span>
                    <span class="nb-stats__kpi-tag">{{ kpi.tag }}</span>
                    <span class="nb-stats__kpi-sub">{{ kpi.sub }}</span>
                </article>
            </div>

            <section class="nb-stats__section nb-stats__rise" style="animation-delay: 320ms">
                <div class="nb-stats__section-head">
                    <span class="nb-stats__badge nb-stats__badge--pink">Думи</span>
                    <h2 class="nb-stats__section-title">Words you've learned</h2>
                    <span class="nb-stats__section-count">{{ learnedWords.length }} unique</span>
                </div>

                <div class="nb-stats__panel nb-stats__panel--cloud">
                    <VueUiWordCloud
                        v-if="wordCloudDataset.length"
                        :dataset="wordCloudDataset"
                        :config="wordCloudConfig"
                    />
                    <p v-else class="nb-stats__empty">No learned words yet.</p>
                </div>
            </section>

            <section class="nb-stats__section nb-stats__rise" style="animation-delay: 400ms">
                <div class="nb-stats__section-head">
                    <span class="nb-stats__badge nb-stats__badge--blue">Активност</span>
                    <h2 class="nb-stats__section-title">Activity by exercise type</h2>
                </div>

                <div class="nb-stats__panel">
                    <VueUiDonutEvolution
                        v-if="activityDataset.length"
                        :dataset="activityDataset"
                        :config="activityConfig"
                    />
                    <p v-else class="nb-stats__empty">No activity yet.</p>
                </div>
            </section>
        </main>
    </div>
</template>
