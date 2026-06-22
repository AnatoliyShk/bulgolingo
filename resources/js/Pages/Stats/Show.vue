<script setup>
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

<style scoped>
/* ── Theme tokens ── */
.pg {
    position: relative;
    min-height: 100vh;
    overflow-x: hidden;
    font-family: 'PT Sans', sans-serif;
    background: var(--bg);
    color: var(--ink);
    transition: background .3s ease, color .3s ease;
}

.pg.light {
    --bg: #fbf6ec;
    --surface: #ffffff;
    --ink: #2b231b;
    --muted: #8a7a66;
    --rose: #b3273e;
    --gold: #b9862e;
    --border: rgba(43, 35, 27, .12);
}

.pg.dark {
    --bg: #1b1712;
    --surface: #27201a;
    --ink: #f3e9d8;
    --muted: #a4937c;
    --rose: #e2697b;
    --gold: #e0b45a;
    --border: rgba(243, 233, 216, .1);
}

/* ── Watermark ── */
.pg__watermark {
    position: fixed;
    top: 50%;
    right: -8vw;
    transform: translateY(-50%);
    font-family: 'PT Serif', serif;
    font-weight: 700;
    font-size: min(70vw, 60rem);
    line-height: 1;
    color: var(--ink);
    opacity: .03;
    pointer-events: none;
    user-select: none;
    z-index: 0;
}

/* ── Top bar ── */
.bar {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    justify-content: space-between;
    max-width: 42rem;
    margin: 0 auto;
    padding: 1.5rem 1.5rem 0;
}

.bar__back,
.bar__theme {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 50%;
    color: var(--muted);
    transition: color .2s ease, border-color .2s ease;
}
.bar__back:hover { color: var(--rose); }
.bar__back-icon { width: 1.1rem; height: 1.1rem; }

.bar__mark {
    font-family: 'PT Serif', serif;
    font-weight: 700;
    font-size: .8rem;
    letter-spacing: .22em;
    text-transform: uppercase;
    color: var(--muted);
}

.bar__theme {
    border: 1px solid var(--border);
    background: var(--surface);
    font-size: 1rem;
    line-height: 1;
    cursor: pointer;
}
.bar__theme:hover { color: var(--rose); border-color: var(--rose); }

/* ── Sheet ── */
.sheet {
    position: relative;
    z-index: 1;
    max-width: 42rem;
    margin: 0 auto;
    padding: 2.75rem 1.5rem 4rem;
}

/* ── Eyebrow ── */
.eyebrow {
    display: flex;
    align-items: baseline;
    gap: .5em;
    margin: 0 0 1.5rem;
}
.eyebrow__bg {
    font-family: 'PT Serif', serif;
    font-weight: 700;
    font-size: .8rem;
    letter-spacing: .14em;
    text-transform: uppercase;
    color: var(--rose);
}
.eyebrow__en {
    font-size: .72rem;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: var(--muted);
}
.eyebrow__en::before { content: '· '; }

/* ── KPI grid ── */
.kpi-grid {
    display: grid;
    gap: 1rem;
    grid-template-columns: 1fr;
    margin-bottom: 0;
}

@media (min-width: 36rem) {
    .kpi-grid { grid-template-columns: repeat(3, 1fr); }
}

.kpi {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: .3rem;
    padding: 1.75rem 1rem;
    border-radius: .85rem;
    background: var(--surface);
    border: 1px solid var(--border);
    text-align: center;
}

.kpi__value {
    font-family: 'PT Serif', serif;
    font-weight: 700;
    font-size: 3rem;
    line-height: 1;
    background: linear-gradient(135deg, var(--rose), var(--gold));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.kpi__bg {
    font-family: 'PT Serif', serif;
    font-weight: 700;
    font-size: .75rem;
    letter-spacing: .12em;
    text-transform: uppercase;
    color: var(--rose);
    margin-top: .35rem;
}

.kpi__en {
    font-size: .7rem;
    letter-spacing: .06em;
    text-transform: uppercase;
    color: var(--muted);
}

/* ── Seam ── */
.seam {
    height: 10px;
    margin: 2.5rem 0;
    background-image:
        repeating-linear-gradient(135deg, var(--rose) 0 4px, transparent 4px 9px),
        repeating-linear-gradient(45deg, var(--gold) 0 4px, transparent 4px 9px);
    opacity: .5;
}

/* ── Activity ── */
.activity-wrap {
    border-radius: .85rem;
    background: var(--surface);
    border: 1px solid var(--border);
    padding: 1.5rem;
    overflow: hidden;
}

/* ── Word cloud ── */
.cloud-wrap {
    border-radius: .85rem;
    background: var(--surface);
    border: 1px solid var(--border);
    padding: 1.5rem;
    overflow: hidden;
    height: 22rem;
}

.empty {
    text-align: center;
    color: var(--muted);
    font-size: .9rem;
    padding: 3rem 0;
}

/* ── Entrance animation ── */
.rise {
    opacity: 0;
    transform: translateY(.75rem);
    transition: opacity .5s ease, transform .5s ease;
}
.rise--in {
    opacity: 1;
    transform: translateY(0);
}

@media (prefers-reduced-motion: reduce) {
    .rise { transition: none; opacity: 1; transform: none; }
}

/* ── Focus ── */
.bar__back:focus-visible,
.bar__theme:focus-visible {
    outline: 2px solid var(--rose);
    outline-offset: 2px;
}
</style>
