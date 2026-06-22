<script setup>
import { onMounted, ref } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import { useTheme } from '@/composables/useTheme'
import { computed } from 'vue'

const props = defineProps({
    paths: Array,
})

const page = usePage()
const appName = computed(() => page.props.appName ?? 'Bulgolingo')

const { theme, toggleTheme } = useTheme()

function start(pathId) {
    router.post(route('learning-paths.start', pathId))
}

const ready = ref(false)
onMounted(() => requestAnimationFrame(() => { ready.value = true }))
</script>

<template>
    <div class="pg" :class="theme">
        <div class="pg__watermark" aria-hidden="true">Ъ</div>

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
            <p class="eyebrow rise" :class="{ 'rise--in': ready }">
                <span class="eyebrow__bg">Пътища</span>
                <span class="eyebrow__en">all learning paths</span>
            </p>

            <div v-if="paths && paths.length" class="grid">
                <div
                    v-for="(path, i) in paths"
                    :key="path.id"
                    class="card rise"
                    :class="{ 'rise--in': ready }"
                    :style="{ transitionDelay: (i * 0.07) + 's' }"
                >
                    <div class="card__head">
                        <h2 class="card__name">{{ path.name }}</h2>
                        <span class="card__tag">{{ path.language }}</span>
                    </div>
                    <button class="card__btn" @click="start(path.id)">Start</button>
                </div>
            </div>

            <p v-else class="empty rise" :class="{ 'rise--in': ready }">
                No learning paths available yet.
            </p>
        </main>
    </div>
</template>

<style scoped>
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
    --forest: #3d6b4f;
    --border: rgba(43, 35, 27, .12);
}

.pg.dark {
    --bg: #1b1712;
    --surface: #27201a;
    --ink: #f3e9d8;
    --muted: #a4937c;
    --rose: #e2697b;
    --gold: #e0b45a;
    --forest: #7cb698;
    --border: rgba(243, 233, 216, .1);
}

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

/* ── Bar ── */
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
.bar__back-icon  { width: 1.1rem; height: 1.1rem; }

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
    margin: 0 0 1.75rem;
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

/* ── Grid ── */
.grid {
    display: grid;
    gap: 1rem;
    grid-template-columns: 1fr;
}
@media (min-width: 36rem) {
    .grid { grid-template-columns: repeat(2, 1fr); }
}

/* ── Card ── */
.card {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    padding: 1.25rem 1.4rem;
    border-radius: .85rem;
    background: var(--surface);
    border: 1px solid var(--border);
    transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease;
}
.card:hover {
    transform: translateY(-3px);
    border-color: var(--rose);
    box-shadow: 0 12px 28px -18px var(--rose);
}

.card__head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: .75rem;
}

.card__name {
    font-family: 'PT Serif', serif;
    font-size: 1.05rem;
    font-weight: 700;
    margin: 0;
    color: var(--ink);
}

.card__tag {
    flex-shrink: 0;
    font-size: .68rem;
    font-weight: 700;
    letter-spacing: .06em;
    text-transform: uppercase;
    color: var(--forest);
    border: 1px solid var(--forest);
    border-radius: 100px;
    padding: .2rem .6rem;
}

.card__btn {
    align-self: flex-start;
    padding: .5rem 1.2rem;
    border-radius: .6rem;
    border: none;
    background: linear-gradient(135deg, var(--rose), var(--gold));
    color: #fff6ea;
    font-family: 'PT Sans', sans-serif;
    font-size: .85rem;
    font-weight: 700;
    cursor: pointer;
    transition: opacity .2s ease, transform .15s ease;
}
.card__btn:hover { opacity: .9; transform: translateY(-1px); }

/* ── Empty ── */
.empty {
    color: var(--muted);
    font-size: .9rem;
    text-align: center;
    padding: 3rem 0;
}

/* ── Entrance ── */
.rise {
    opacity: 0;
    transform: translateY(.75rem);
    transition: opacity .5s ease, transform .5s ease;
}
.rise--in { opacity: 1; transform: translateY(0); }

@media (prefers-reduced-motion: reduce) {
    .rise { transition: none; opacity: 1; transform: none; }
}

.bar__back:focus-visible,
.bar__theme:focus-visible,
.card__btn:focus-visible {
    outline: 2px solid var(--rose);
    outline-offset: 2px;
}
</style>
