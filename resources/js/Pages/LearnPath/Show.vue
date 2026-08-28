<script setup>
import '@/assets/scss/components/learn-path/show.scss'
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import { useTheme } from '@/composables/useTheme'

const props = defineProps({
    learningPath: Object,
    lessons: Array,
})

const { theme, toggleTheme } = useTheme()
const page    = usePage()
const isAdmin = computed(() => page.props.auth.isAdmin)

// ── Map geometry ──
const ROW_GAP = 132   // vertical px between nodes
const TOP_PAD = 88    // px above the first node (room for the "you are here" pin)
const NODE_R  = 38    // default node radius
const BOSS_R  = 50    // final ("boss") node radius

// The stage width drives the horizontal swing; tracked so the path stays
// inside the container on every viewport.
const mapEl = ref(null)
const width = ref(360)
let resizeObserver = null

onMounted(() => {
    if (!mapEl.value) return
    width.value = mapEl.value.clientWidth
    resizeObserver = new ResizeObserver(([entry]) => {
        width.value = entry.contentRect.width
    })
    resizeObserver.observe(mapEl.value)
})
onBeforeUnmount(() => resizeObserver?.disconnect())

// First lesson that isn't completed yet — that's where the player "is".
const currentIndex = computed(() =>
    props.lessons.findIndex(l => !l.pivot?.is_completed)
)

const nodes = computed(() => {
    const w   = width.value
    const n   = props.lessons.length
    const amp = Math.min(w * 0.30, 120)

    return props.lessons.map((lesson, i) => {
        const isBoss = n > 1 && i === n - 1
        const r      = isBoss ? BOSS_R : NODE_R
        const margin = r + 8

        let x = w / 2 + Math.sin(i * 0.9 + 0.6) * amp
        x = Math.max(margin, Math.min(w - margin, x))
        const y = TOP_PAD + i * ROW_GAP

        // Every lesson stays openable; status only drives the map's visuals.
        const done   = !!lesson.pivot?.is_completed
        const status = done ? 'done' : (currentIndex.value === i ? 'current' : 'upcoming')
        const side   = x < w / 2 ? 'right' : 'left'

        return { lesson, i, x, y, r, isBoss, status, side, num: i + 1 }
    })
})

const segments = computed(() => {
    const ns  = nodes.value
    const out = []
    for (let i = 0; i < ns.length - 1; i++) {
        out.push({
            x1: ns[i].x,   y1: ns[i].y,
            x2: ns[i + 1].x, y2: ns[i + 1].y,
            done: ns[i].status === 'done',
        })
    }
    return out
})

const mapHeight = computed(() =>
    TOP_PAD + Math.max(0, props.lessons.length - 1) * ROW_GAP + BOSS_R + 96
)

function glyph(node) {
    if (node.isBoss)            return 'Final'
    if (node.status === 'done') return '✓'
    return String(node.num)
}

function nodeStyle(node) {
    return {
        left:   node.x + 'px',
        top:    node.y + 'px',
        width:  node.r * 2 + 'px',
        height: node.r * 2 + 'px',
    }
}

function labelStyle(node) {
    const gap = node.r + 16
    const base = { top: node.y + 'px' }
    if (node.side === 'right') {
        base.left     = (node.x + gap) + 'px'
        base.maxWidth = Math.max(120, width.value - node.x - gap - 8) + 'px'
    } else {
        base.right    = (width.value - node.x + gap) + 'px'
        base.maxWidth = Math.max(120, node.x - gap - 8) + 'px'
    }
    return base
}
</script>

<template>
    <Head>
        <link
            href="https://fonts.bunny.net/css?family=unbounded:400,600,700,800,900|manrope:400,500,600,700,800&subset=cyrillic,latin&display=swap"
            rel="stylesheet"
        />
    </Head>

    <div class="lp" :class="theme">
        <header class="lp__bar">
            <Link :href="route('dashboard')" class="lp__btn" aria-label="Back to dashboard">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
                </svg>
            </Link>
            <span class="lp__bar-spacer" />
            <button
                class="lp__btn lp__btn--toggle"
                @click="toggleTheme"
                :title="theme === 'dark' ? 'Switch to light' : 'Switch to dark'"
            >{{ theme === 'dark' ? '☀' : '☾' }}</button>
        </header>

        <div class="lp__head">
            <span class="lp__kicker">Твоят път · Your path</span>
            <h1 class="lp__title">{{ learningPath.name }}</h1>
            <span class="lp__lang">{{ learningPath.language }}</span>

            <div class="lp__legend">
                <span class="lp__legend-item"><span class="lp__legend-dot lp__legend-dot--done" /> Done</span>
                <span class="lp__legend-item"><span class="lp__legend-dot lp__legend-dot--current" /> You are here</span>
                <span class="lp__legend-item"><span class="lp__legend-dot lp__legend-dot--upcoming" /> Up next</span>
            </div>
        </div>

        <div v-if="lessons.length" ref="mapEl" class="stmap" :style="{ height: mapHeight + 'px' }">
            <svg class="stmap__lines" :viewBox="`0 0 ${width} ${mapHeight}`" preserveAspectRatio="none">
                <line
                    v-for="(seg, i) in segments"
                    :key="i"
                    :x1="seg.x1" :y1="seg.y1" :x2="seg.x2" :y2="seg.y2"
                    class="stmap__seg"
                    :class="seg.done ? 'stmap__seg--done' : 'stmap__seg--todo'"
                />
            </svg>

            <template v-for="node in nodes" :key="node.lesson.id">
                <!-- Label card -->
                <Link
                    :href="route('lesson.show', node.lesson.id)"
                    class="stmap__label"
                    :class="`stmap__label--${node.side}`"
                    :style="labelStyle(node)"
                >
                    <span class="stmap__label-title">{{ node.lesson.description || node.lesson.name }}</span>
                    <span v-if="node.lesson.description" class="stmap__label-sub">{{ node.lesson.name }}</span>
                </Link>

                <!-- Node token -->
                <Link
                    :href="route('lesson.show', node.lesson.id)"
                    class="stmap__node"
                    :class="[`stmap__node--${node.status}`, { 'stmap__node--boss': node.isBoss }]"
                    :style="nodeStyle(node)"
                    :aria-label="node.lesson.description || node.lesson.name"
                >
                    <span v-if="node.status === 'current'" class="stmap__pin">Тук · Here</span>
                    <span class="stmap__node-glyph">{{ glyph(node) }}</span>
                    <span v-if="node.i === 0" class="stmap__tag">Старт</span>
                    <span v-else-if="node.isBoss" class="stmap__tag stmap__tag--boss">Финал</span>
                </Link>
            </template>
        </div>

        <div v-else class="lp__empty">
            No lessons on this path yet.
        </div>

        <div v-if="isAdmin" class="lp__create-wrap">
            <Link :href="route('lesson.create')" class="lp__create">+ Create Lesson</Link>
        </div>
    </div>
</template>
