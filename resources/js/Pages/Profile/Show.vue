<!-- resources/js/Pages/Profile/Show.vue -->
<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { useTheme } from '@/composables/useTheme'
import { useForm } from '@inertiajs/vue3'

const logoutForm = useForm({})

const props = defineProps({
    user: Object,
    learningPaths: {
        type: Array,
        default: () => [],
    },
    appName: {
        type: String,
        default: 'Bulgolingo',
    },
})

const { theme, toggleTheme } = useTheme()

const initial = computed(() => {
    const name = props.user?.name?.trim() ?? ''
    return name ? Array.from(name)[0].toUpperCase() : '?'
})

const memberSince = computed(() => {
    if (!props.user?.created_at) return null
    return new Intl.DateTimeFormat('en', { month: 'long', year: 'numeric' }).format(new Date(props.user.created_at))
})

function progressPercent(path) {
    const total = path.lessons_count ?? 0
    if (!total) return 0
    return Math.round(((path.completed_lessons_count ?? 0) / total) * 100)
}
</script>

<template>
    <div class="dash" :class="theme">
        <div class="dash__watermark" aria-hidden="true">Ъ</div>

        <header class="bar">
            <Link href="/" class="bar__back" aria-label="Back to home">
                <svg class="bar__back-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </Link>
            <span class="bar__mark">{{ appName }}</span>
            <div class="bar__right">
                <button
                    class="bar__theme"
                    @click="toggleTheme"
                    :aria-label="theme === 'dark' ? 'Switch to light theme' : 'Switch to dark theme'"
                >{{ theme === 'dark' ? '☀️' : '🌙' }}</button>
                <button
                    class="bar__logout"
                    @click="logoutForm.post(route('logout'))"
                    aria-label="Log out"
                >Log out</button>
            </div>
        </header>

        <main class="sheet">
            <section class="id">
                <div class="seal">
                    <span class="seal__letter">{{ initial }}</span>
                </div>
                <div class="id__text">
                    <p class="eyebrow">
                        <span class="eyebrow__bg">Профил</span>
                        <span class="eyebrow__en">profile</span>
                    </p>
                    <h1 class="id__name">{{ user.name }}</h1>
                    <p class="id__line">{{ user.email }}</p>
                    <p v-if="memberSince" class="id__line id__line--muted">Learning Bulgarian since {{ memberSince }}</p>
                </div>

                <Link :href="route('profile.edit')" class="id__edit" aria-label="Edit profile">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </Link>
            </section>

            <div class="seam" aria-hidden="true"></div>

            <section class="paths">
                <p class="eyebrow">
                    <span class="eyebrow__bg">Пътят</span>
                    <span class="eyebrow__en">your path</span>
                </p>

                <div v-if="learningPaths.length" class="paths__grid">
                    <Link
                        v-for="path in learningPaths"
                        :key="path.id"
                        :href="path.continue_lesson_id ? route('lesson.show', path.continue_lesson_id) : route('learning-paths.show', path.id)"
                        class="path"
                    >
                        <div class="path__head">
                            <h2 class="path__name">{{ path.name }}</h2>
                            <span class="path__tag">{{ path.language }}</span>
                        </div>
                        <div v-if="path.exercise_types?.length" class="path__types">
                            <span v-for="t in path.exercise_types" :key="t" class="path__type">{{ t }}</span>
                        </div>

                        <div class="path__track">
                            <div class="path__fill" :style="{ width: progressPercent(path) + '%' }"></div>
                        </div>
                        <div class="path__foot">
                            <span>{{ path.completed_lessons_count ?? 0 }} / {{ path.lessons_count ?? 0 }} lessons</span>
                            <span class="path__btn">Continue</span>
                        </div>
                    </Link>
                </div>

                <div v-else class="paths__empty">
                    <p class="paths__empty-bg">Няма избран път</p>
                    <p class="paths__empty-en">You haven't picked a learning path yet.</p>
                    <Link :href="route('learning-paths.index')" class="btn">Choose a path</Link>
                </div>
            </section>

            <div class="seam" aria-hidden="true"></div>

            <Link :href="route('stats.show')" class="stats">
                <span class="eyebrow">
                    <span class="eyebrow__bg">Статистика</span>
                    <span class="eyebrow__en">your stats</span>
                </span>
                <span class="stats__arrow" aria-hidden="true">→</span>
            </Link>
        </main>
    </div>
</template>

<style scoped>
.dash {
    position: relative;
    min-height: 100vh;
    overflow-x: hidden;
    font-family: 'PT Sans', sans-serif;
    background: var(--bg);
    color: var(--ink);
    transition: background .3s ease, color .3s ease;
}

.dash.light {
    --bg: #fbf6ec;
    --surface: #ffffff;
    --ink: #2b231b;
    --muted: #8a7a66;
    --rose: #b3273e;
    --gold: #b9862e;
    --forest: #3d6b4f;
    --border: rgba(43, 35, 27, .12);
}

.dash.dark {
    --bg: #1b1712;
    --surface: #27201a;
    --ink: #f3e9d8;
    --muted: #a4937c;
    --rose: #e2697b;
    --gold: #e0b45a;
    --forest: #7cb698;
    --border: rgba(243, 233, 216, .1);
}

/* ── Watermark ── */
.dash__watermark {
    position: fixed;
    top: 50%;
    right: -8vw;
    transform: translateY(-50%);
    font-family: 'PT Serif', serif;
    font-weight: 700;
    font-size: min(70vw, 60rem);
    line-height: 1;
    color: var(--ink);
    opacity: .04;
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
    transition: color .2s ease, border-color .2s ease, background .2s ease;
}

.bar__back-icon {
    width: 1.1rem;
    height: 1.1rem;
}

.bar__back:hover {
    color: var(--rose);
}

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

.bar__theme:hover {
    color: var(--rose);
    border-color: var(--rose);
}

.bar__right {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.bar__logout {
    height: 2.25rem;
    padding: 0 0.85rem;
    border-radius: 999px;
    border: 1px solid var(--border);
    background: var(--surface);
    color: var(--muted);
    font-family: 'PT Sans', sans-serif;
    font-size: 0.8rem;
    font-weight: 700;
    letter-spacing: 0.03em;
    cursor: pointer;
    transition: color .2s ease, border-color .2s ease, background .2s ease;
}

.bar__logout:hover {
    color: var(--rose);
    border-color: var(--rose);
}

/* ── Page ── */
.sheet {
    position: relative;
    z-index: 1;
    max-width: 42rem;
    margin: 0 auto;
    padding: 2.75rem 1.5rem 4rem;
}

/* ── Identity ── */
.id {
    display: flex;
    align-items: center;
    gap: 1.75rem;
    flex-wrap: wrap;
    animation: rise .6s ease both;
}

.seal {
    flex-shrink: 0;
    position: relative;
    width: 5.25rem;
    height: 5.25rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(140deg, var(--rose), var(--gold));
    box-shadow: 0 10px 28px -12px var(--rose);
    animation: stamp .55s cubic-bezier(.34, 1.56, .64, 1) .15s both;
}

.seal::before {
    content: '';
    position: absolute;
    inset: 5px;
    border-radius: 50%;
    border: 1px dashed rgba(255, 255, 255, .55);
}

.seal__letter {
    font-family: 'PT Serif', serif;
    font-weight: 700;
    font-size: 2.25rem;
    color: #fff6ea;
}

.id__text {
    min-width: 0;
}

.id__name {
    font-family: 'PT Serif', serif;
    font-weight: 700;
    font-size: 1.8rem;
    line-height: 1.2;
    margin: 0 0 .35rem;
    word-break: break-word;
}

.id__line {
    font-size: .9rem;
    margin: 0;
}

.id__edit {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    margin-left: auto;
    align-self: center;
    width: 3rem;
    height: 3rem;
    border-radius: .6rem;
    border: 1px solid var(--border);
    background: var(--surface);
    color: var(--muted);
    text-decoration: none;
    transition: color .2s ease, border-color .2s ease;
}
.id__edit svg { width: 1.4rem; height: 1.4rem; }
.id__edit:hover { color: var(--rose); border-color: var(--rose); }

.id__line--muted {
    color: var(--muted);
    font-size: .8rem;
    margin-top: .2rem;
}

/* ── Eyebrows ── */
.eyebrow {
    display: flex;
    align-items: baseline;
    gap: .5em;
    margin: 0 0 .85rem;
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

.eyebrow__en::before {
    content: '· ';
}

/* ── Seam (folk embroidery divider) ── */
.seam {
    height: 10px;
    margin: 2.5rem 0;
    background-image:
        repeating-linear-gradient(135deg, var(--rose) 0 4px, transparent 4px 9px),
        repeating-linear-gradient(45deg, var(--gold) 0 4px, transparent 4px 9px);
    opacity: .5;
    animation: rise .6s ease .1s both;
}

/* ── Learning paths ── */
.paths {
    animation: rise .6s ease .2s both;
}

.paths__grid {
    display: grid;
    gap: 1rem;
    grid-template-columns: 1fr;
}


.path {
    display: flex;
    flex-direction: column;
    gap: .75rem;
    padding: 1.1rem 1.25rem;
    border-radius: .85rem;
    background: var(--surface);
    border: 1px solid var(--border);
    text-decoration: none;
    color: var(--ink);
    transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease;
}

.path:hover {
    transform: translateY(-3px);
    border-color: var(--rose);
    box-shadow: 0 12px 28px -18px var(--rose);
}

.path__head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: .75rem;
}

.path__name {
    font-family: 'PT Serif', serif;
    font-size: 1.05rem;
    font-weight: 700;
    margin: 0;
}

.path__tag {
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

.path__track {
    height: 6px;
    border-radius: 100px;
    background: var(--border);
    overflow: hidden;
}

.path__fill {
    height: 100%;
    border-radius: 100px;
    background: linear-gradient(90deg, var(--rose), var(--gold));
    transition: width .8s ease .3s;
}

.path__types {
    display: flex;
    flex-wrap: wrap;
    gap: .35rem;
}

.path__type {
    font-size: .62rem;
    font-weight: 700;
    letter-spacing: .05em;
    text-transform: uppercase;
    color: var(--gold);
    border: 1px solid var(--gold);
    border-radius: 100px;
    padding: .15rem .5rem;
    opacity: .85;
}

.path__foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: .78rem;
    color: var(--muted);
}

.path__btn {
    display: inline-block;
    padding: .45rem 1.1rem;
    border-radius: .55rem;
    background: linear-gradient(135deg, var(--rose), var(--gold));
    color: #fff6ea;
    font-size: .8rem;
    font-weight: 700;
    letter-spacing: .03em;
    transition: opacity .2s ease, transform .15s ease;
}

.path:hover .path__btn {
    opacity: .9;
    transform: translateY(-1px);
}

/* ── Lesson list ── */
.path__lessons {
    list-style: none;
    margin: .5rem 0 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: .35rem;
    border-top: 1px solid var(--border);
    padding-top: .75rem;
}

.path__lesson {
    display: flex;
    align-items: center;
    gap: .55rem;
    font-size: .78rem;
    color: var(--muted);
}

.path__lesson-dot {
    flex-shrink: 0;
    width: .55rem;
    height: .55rem;
    border-radius: 50%;
    border: 1.5px solid var(--muted);
    background: transparent;
    transition: background .2s ease, border-color .2s ease;
}

.path__lesson--done .path__lesson-dot {
    background: var(--forest);
    border-color: var(--forest);
}

.path__lesson--done .path__lesson-name {
    color: var(--ink);
}

/* ── Empty state ── */
.paths__empty {
    text-align: center;
    padding: 2.5rem 1.5rem;
    border: 1px dashed var(--border);
    border-radius: .85rem;
}

.paths__empty-bg {
    font-family: 'PT Serif', serif;
    font-size: 1.15rem;
    font-weight: 700;
    margin: 0 0 .25rem;
}

.paths__empty-en {
    font-size: .82rem;
    color: var(--muted);
    margin: 0 0 1.25rem;
}

.btn {
    display: inline-block;
    padding: .6rem 1.6rem;
    border-radius: .6rem;
    background: linear-gradient(135deg, var(--rose), var(--gold));
    color: #fff6ea;
    font-weight: 700;
    font-size: .85rem;
    text-decoration: none;
    transition: opacity .2s ease, transform .2s ease;
}

.btn:hover {
    opacity: .9;
    transform: translateY(-1px);
}

/* ── Stats link ── */
.stats {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    text-decoration: none;
    color: var(--ink);
    animation: rise .6s ease .3s both;
}

.stats .eyebrow {
    margin: 0;
}

.stats__arrow {
    font-size: 1.2rem;
    color: var(--rose);
    transition: transform .2s ease;
}

.stats:hover .stats__arrow {
    transform: translateX(.3rem);
}

/* ── Motion ── */
@keyframes rise {
    from { opacity: 0; transform: translateY(.75rem); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes stamp {
    from { opacity: 0; transform: scale(.55) rotate(-14deg); }
    to { opacity: 1; transform: scale(1) rotate(0deg); }
}

@media (prefers-reduced-motion: reduce) {
    .id, .seam, .paths, .stats, .seal {
        animation: none;
    }
}

/* ── Focus ── */
.bar__back:focus-visible,
.bar__theme:focus-visible,
.path:focus-visible,
.stats:focus-visible,
.btn:focus-visible {
    outline: 2px solid var(--rose);
    outline-offset: 2px;
}
</style>
