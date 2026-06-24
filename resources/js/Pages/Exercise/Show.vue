<script setup>
import { ref, computed, watch } from 'vue'
import { usePage, router, Link } from '@inertiajs/vue3'
import { useTheme } from '@/composables/useTheme'
import UpdateExerciseForm from '@/Components/Forms/UpdateExerciseForm.vue'
import FillInTheBlank from './FillInTheBlank.vue'
import ImageMatching from './ImageMatching.vue'
import MultipleChoice from './MultipleChoice.vue'
import TrueFalse from './TrueFalse.vue'

const { theme, toggleTheme } = useTheme()

const props = defineProps({
    exercise:       { type: Object, required: true },
    exerciseTypes:  { type: Array,  required: true },
    totalExercises: { type: Number, default: 0 },
    completedCount: { type: Number, default: 0 },
})

const page    = usePage()
const isAdmin = computed(() => page.props.auth.isAdmin)

const showForm = ref(false)
watch(() => props.exercise.id, () => { showForm.value = false })

const progressPct = computed(() =>
    props.totalExercises > 0 ? (props.completedCount / props.totalExercises) * 100 : 0
)
const remaining = computed(() => props.totalExercises - props.completedCount)

const typeLabel = computed(() => {
    const match = props.exerciseTypes.find(t => t.value === props.exercise.decision_type)
    return match?.label ?? props.exercise.decision_type ?? ''
})

const exerciseComponent = computed(() => {
    switch (props.exercise.decision_type) {
        case 'fill_in_the_blank': return FillInTheBlank
        case 'image_matching':    return ImageMatching
        case 'multiple_choice':   return MultipleChoice
        case 'true_false':        return TrueFalse
        default:                  return null
    }
})

const exerciseImageUrl = computed(() => props.exercise.images?.[0]?.url ?? null)

function onComplete() {
    router.post(route('exercise.complete', props.exercise.id))
}
</script>

<template>
    <div class="ex" :class="theme">
        <div class="ex__watermark" aria-hidden="true">Ъ</div>

        <header class="bar">
            <Link :href="route('dashboard')" class="bar__back" aria-label="Back to dashboard">
                <svg class="bar__back-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </Link>

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
            <p class="eyebrow">
                <span class="eyebrow__bg">Упражнение</span>
                <span class="eyebrow__en">{{ typeLabel }} · {{ remaining }} remaining</span>
            </p>

            <component
                v-if="exerciseComponent"
                :is="exerciseComponent"
                :clause="exercise.clause"
                :image-url="exerciseImageUrl"
                @complete="onComplete"
            />

            <div v-else class="unsupported">
                Exercise type <strong>{{ typeLabel }}</strong> is not yet supported in the player.
            </div>

            <div v-if="isAdmin" class="edit-toggle">
                <button class="edit-toggle__btn" @click="showForm = !showForm">
                    {{ showForm ? 'Cancel' : 'Edit Exercise' }}
                </button>
                <UpdateExerciseForm
                    v-if="showForm"
                    :exercise="exercise"
                    :exercise-types="exerciseTypes"
                    :submit-route="route('admin.exercises.update', exercise.id)"
                    @success="showForm = false"
                    @cancel="showForm = false"
                />
            </div>
        </main>
    </div>
</template>

<style scoped>
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
.bar__back:hover  { color: var(--rose); }
.bar__back-icon   { width: 1.1rem; height: 1.1rem; }

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

.sheet {
    position: relative;
    z-index: 1;
    max-width: 36rem;
    margin: 0 auto;
    padding: 2.5rem 1.5rem 4rem;
}

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

.unsupported {
    padding: 2rem;
    text-align: center;
    color: var(--muted);
    font-size: .9rem;
    border: 1px dashed var(--border);
    border-radius: .85rem;
}

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

.bar__back:focus-visible,
.bar__theme:focus-visible {
    outline: 2px solid var(--rose);
    outline-offset: 2px;
}

@media (prefers-reduced-motion: reduce) {
    .bar__fill { transition: none; }
}
</style>
