<script setup>
import { ref, computed, reactive, watch } from 'vue'
import { usePage, router, Link } from '@inertiajs/vue3'
import UpdateExerciseForm from "@/Components/Forms/UpdateExerciseForm.vue";
import { useTheme } from '@/composables/useTheme';

const { theme, toggleTheme } = useTheme();

const props = defineProps({
    exercise: {
        type: Object,
        required: true,
    },
    exerciseTypes: {
        type: Array,
        required: true,
    },
    totalExercises: {
        type: Number,
        default: 0,
    },
    completedCount: {
        type: Number,
        default: 0,
    },
})

const showForm = ref(false);

const shuffle = (arr) => [...arr].sort(() => Math.random() - 0.5)

function clauseToQuestion(clause) {
    const parts = (clause.sentence ?? '').split(/\s+/)
    const blank = parts.findIndex(w => w === '__')
    const options = clause.options ?? []
    return {
        template: blank === -1 ? [...parts, '__'] : parts,
        blank:    blank === -1 ? parts.length : blank,
        answer:   options[clause.correct_option ?? 0] ?? '',
        choices:  options,
        translation: clause.explanation ?? '',
    }
}

const questions = computed(() => [clauseToQuestion(props.exercise.clause ?? {})])

const state = reactive({
    current: 0,
    selected: null,
    checked: false,
    score: 0,
    streak: 0,
})

const shuffledChoices = ref(questions.value.map((q) => shuffle(q.choices)))

watch(() => props.exercise.id, () => {
    shuffledChoices.value = questions.value.map(q => shuffle(q.choices))
    state.current = 0
    state.selected = null
    state.checked = false
})

const question    = computed(() => questions.value[state.current])
const isCorrect   = computed(() => state.checked && state.selected === question.value.answer)
const progressPct = computed(() => props.totalExercises > 0 ? (props.completedCount / props.totalExercises) * 100 : 0)
const remaining   = computed(() => props.totalExercises - props.completedCount)

function selectChoice(word) {
    if (state.checked) return
    state.selected = word
}

function removeSelected() {
    if (state.checked) return
    state.selected = null
}

function check() {
    if (!state.selected || state.checked) return
    state.checked = true
    if (isCorrect.value) {
        state.score += 10 + state.streak * 2
        state.streak++
        router.post(route('exercise.complete', props.exercise.id))
    } else {
        state.streak = 0
    }
}

function retry() {
    state.selected = null
    state.checked = false
}

const page = usePage()
const isAdmin = computed(() => page.props.auth.isAdmin)
</script>

<template>
    <div class="ex" :class="theme">
        <div class="ex__watermark" aria-hidden="true">Ъ</div>

        <!-- Top bar -->
        <header class="bar">
            <Link :href="route('dashboard')" class="bar__back" aria-label="Back to dashboard">
                <svg class="bar__back-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </Link>

            <!-- Progress bar -->
            <div class="bar__progress">
                <div class="bar__track">
                    <div class="bar__fill" :style="{ width: progressPct + '%' }" />
                </div>
                <span class="bar__count">{{ completedCount }} / {{ totalExercises }}</span>
            </div>

            <button class="bar__theme" @click="toggleTheme" :title="theme === 'dark' ? 'Switch to light' : 'Switch to dark'">
                {{ theme === 'dark' ? '☀️' : '🌙' }}
            </button>
        </header>

        <main class="sheet">
            <!-- Eyebrow label -->
            <p class="eyebrow">
                <span class="eyebrow__bg">Упражнение</span>
                <span class="eyebrow__en">fill in the blank · {{ remaining }} remaining</span>
            </p>

            <!-- Translation / prompt -->
            <p class="prompt">{{ question.translation }}</p>

            <!-- Sentence with blank -->
            <div class="sentence">
                <template v-for="(word, i) in question.template" :key="i">
                    <span
                        v-if="i === question.blank"
                        :class="['blank', { 'blank--filled': state.selected, 'blank--correct': isCorrect, 'blank--wrong': state.checked && !isCorrect }]"
                        @click="removeSelected"
                    >
                        <span v-if="state.selected" class="blank__word">{{ state.selected }}</span>
                        <span v-else class="blank__hint">tap a word</span>
                    </span>
                    <span v-else class="sentence__word">{{ word }}</span>
                </template>
            </div>

            <!-- Word choices -->
            <div class="choices">
                <button
                    v-for="word in shuffledChoices[state.current]"
                    :key="word"
                    class="choice"
                    :class="{ 'choice--used': state.selected === word || state.checked }"
                    :disabled="state.selected === word || state.checked"
                    @click="selectChoice(word)"
                >
                    {{ word }}
                </button>
            </div>

            <!-- Wrong answer feedback -->
            <div v-if="state.checked && !isCorrect" class="feedback feedback--wrong">
                <p class="feedback__title">✗ Incorrect — try again</p>
                <p class="feedback__body">Correct answer: <strong>{{ question.answer }}</strong></p>
            </div>

            <!-- Action button -->
            <button
                class="btn-action"
                :class="{ 'btn-action--disabled': !state.selected && !state.checked }"
                :disabled="!state.selected && !state.checked"
                @click="state.checked ? retry() : check()"
            >
                {{ state.checked ? (isCorrect ? 'Next Exercise' : 'Try Again') : 'Check' }}
            </button>

            <!-- Admin edit -->
            <div v-if="isAdmin" class="edit-toggle">
                <button class="edit-toggle__btn" @click="showForm = !showForm">
                    {{ showForm ? 'Cancel' : 'Edit Exercise' }}
                </button>
                <UpdateExerciseForm
                    v-if="showForm"
                    :exercise="exercise"
                    :exercise-types="exerciseTypes"
                    @success="showForm = false"
                    @cancel="showForm = false"
                />
            </div>
        </main>
    </div>
</template>

<style scoped>
/* ── Theme tokens ── */
.ex {
    position: relative;
    min-height: 100vh;
    overflow-x: hidden;
    font-family: 'PT Sans', sans-serif;
    background: var(--bg);
    color: var(--ink);
    transition: background .3s ease, color .3s ease;
}

.ex.light {
    --bg: #fbf6ec;
    --surface: #ffffff;
    --ink: #2b231b;
    --muted: #8a7a66;
    --rose: #b3273e;
    --gold: #b9862e;
    --forest: #3d6b4f;
    --forest-bg: #edf5f0;
    --rose-bg: #fdf0f2;
    --border: rgba(43, 35, 27, .12);
}

.ex.dark {
    --bg: #1b1712;
    --surface: #27201a;
    --ink: #f3e9d8;
    --muted: #a4937c;
    --rose: #e2697b;
    --gold: #e0b45a;
    --forest: #7cb698;
    --forest-bg: #1a2e25;
    --rose-bg: #2e1a1d;
    --border: rgba(243, 233, 216, .1);
}

/* ── Watermark ── */
.ex__watermark {
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
    gap: 1rem;
    max-width: 36rem;
    margin: 0 auto;
    padding: 1.25rem 1.5rem 0;
}

.bar__back {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 50%;
    color: var(--muted);
    transition: color .2s ease;
}
.bar__back:hover { color: var(--rose); }
.bar__back-icon { width: 1.1rem; height: 1.1rem; }

.bar__progress {
    flex: 1;
    display: flex;
    align-items: center;
    gap: .75rem;
}

.bar__track {
    flex: 1;
    height: 7px;
    border-radius: 100px;
    background: var(--border);
    overflow: hidden;
}

.bar__fill {
    height: 100%;
    border-radius: 100px;
    background: linear-gradient(90deg, var(--rose), var(--gold));
    transition: width .6s ease;
}

.bar__count {
    flex-shrink: 0;
    font-size: .72rem;
    font-weight: 700;
    color: var(--muted);
    letter-spacing: .04em;
}

.bar__theme {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 50%;
    border: 1px solid var(--border);
    background: var(--surface);
    font-size: 1rem;
    line-height: 1;
    cursor: pointer;
    color: var(--muted);
    transition: color .2s ease, border-color .2s ease;
}
.bar__theme:hover { color: var(--rose); border-color: var(--rose); }

/* ── Sheet ── */
.sheet {
    position: relative;
    z-index: 1;
    max-width: 36rem;
    margin: 0 auto;
    padding: 2.5rem 1.5rem 4rem;
}

/* ── Eyebrow ── */
.eyebrow {
    display: flex;
    align-items: baseline;
    gap: .5em;
    margin: 0 0 1rem;
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

/* ── Prompt ── */
.prompt {
    font-family: 'PT Serif', serif;
    font-size: 1.5rem;
    font-weight: 700;
    line-height: 1.35;
    color: var(--ink);
    margin: 0 0 2rem;
}

/* ── Sentence ── */
.sentence {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: .4rem .5rem;
    padding-bottom: 1.25rem;
    border-bottom: 2px solid var(--border);
    margin-bottom: 1.75rem;
    min-height: 3rem;
}

.sentence__word {
    font-size: 1.15rem;
    color: var(--ink);
}

.blank {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 5.5rem;
    height: 2.25rem;
    border-bottom: 3px solid var(--muted);
    padding: 0 .6rem;
    cursor: pointer;
    transition: border-color .2s ease;
}

.blank--filled { border-bottom-color: var(--gold); }
.blank--correct { border-bottom-color: var(--forest); }
.blank--wrong   { border-bottom-color: var(--rose); }

.blank__word {
    font-size: 1rem;
    font-weight: 700;
    color: var(--gold);
    transition: color .2s ease;
}

.blank--correct .blank__word { color: var(--forest); }
.blank--wrong   .blank__word { color: var(--rose); }

.blank__hint {
    font-size: .75rem;
    color: var(--muted);
    letter-spacing: .04em;
}

/* ── Choices ── */
.choices {
    display: flex;
    flex-wrap: wrap;
    gap: .65rem;
    margin-bottom: 2rem;
}

.choice {
    padding: .6rem 1.25rem;
    border-radius: .6rem;
    border: 1.5px solid var(--border);
    background: var(--surface);
    color: var(--ink);
    font-family: 'PT Sans', sans-serif;
    font-size: .95rem;
    font-weight: 600;
    cursor: pointer;
    transition: border-color .15s ease, transform .1s ease, color .15s ease;
}

.choice:hover:not(:disabled) {
    border-color: var(--rose);
    color: var(--rose);
    transform: translateY(-2px);
}

.choice--used,
.choice:disabled {
    opacity: .35;
    cursor: default;
    transform: none;
}

/* ── Feedback ── */
.feedback {
    border-radius: .75rem;
    padding: .85rem 1.1rem;
    margin-bottom: 1.25rem;
    border: 1px solid transparent;
}

.feedback--wrong {
    background: var(--rose-bg);
    border-color: var(--rose);
    color: var(--rose);
}

.feedback__title {
    font-weight: 700;
    font-size: .9rem;
    margin: 0 0 .25rem;
}

.feedback__body {
    font-size: .85rem;
    margin: 0;
    opacity: .85;
}

/* ── Action button ── */
.btn-action {
    width: 100%;
    padding: .9rem 1.5rem;
    border-radius: .75rem;
    border: none;
    background: linear-gradient(135deg, var(--rose), var(--gold));
    color: #fff6ea;
    font-family: 'PT Sans', sans-serif;
    font-size: 1rem;
    font-weight: 700;
    letter-spacing: .03em;
    cursor: pointer;
    transition: opacity .2s ease, transform .15s ease;
}

.btn-action:hover:not(:disabled) {
    opacity: .9;
    transform: translateY(-2px);
}

.btn-action--disabled,
.btn-action:disabled {
    background: var(--border);
    color: var(--muted);
    cursor: default;
    transform: none;
    opacity: 1;
}

/* ── Admin edit ── */
.edit-toggle {
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--border);
}

.edit-toggle__btn {
    background: none;
    border: 1.5px solid var(--border);
    border-radius: .5rem;
    color: var(--muted);
    font-size: .82rem;
    font-weight: 600;
    padding: .4rem .9rem;
    cursor: pointer;
    transition: border-color .15s ease, color .15s ease;
}
.edit-toggle__btn:hover { border-color: var(--rose); color: var(--rose); }

/* ── Focus ── */
.bar__back:focus-visible,
.bar__theme:focus-visible,
.choice:focus-visible,
.btn-action:focus-visible {
    outline: 2px solid var(--rose);
    outline-offset: 2px;
}

/* ── Motion ── */
@media (prefers-reduced-motion: reduce) {
    .bar__fill, .blank, .choice, .btn-action { transition: none; }
}
</style>
