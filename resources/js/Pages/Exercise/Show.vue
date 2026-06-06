<script setup>
import { ref, computed, reactive } from 'vue'
import { usePage, router, Link } from '@inertiajs/vue3'
import UpdateExerciseForm from "@/Components/Forms/UpdateExerciseForm.vue";
import { useTheme } from '@/composables/useTheme';

const { theme, toggleTheme } = useTheme();

const props = defineProps({
    lesson: {
        type: Object,
        required: true,
    },
    exercise: {
        type: Object,
        required: true,
    },
    exerciseTypes: {
        type: Array,
        required: true,
    },
    nextExerciseId: {
        type: Number,
        default: null,
    },
})

const showForm = ref(false);

const shuffle = (arr) => [...arr].sort(() => Math.random() - 0.5)

function clauseToQuestion(clause) {
    const parts = (clause.sentence ?? '').split(/\s+/)
    const blank = parts.findIndex(w => w === '___')
    const options = clause.options ?? []
    return {
        template: blank === -1 ? [...parts, '___'] : parts,
        blank:    blank === -1 ? parts.length : blank,
        answer:   options[clause.correct_option ?? 0] ?? '',
        choices:  options,
        translation: clause.explanation ?? '',
    }
}

const questions = [clauseToQuestion(props.exercise.clause ?? {})]
console.log(questions)
const state = reactive({
    current: 0,
    selected: null,
    checked: false,
    score: 0,
    lives: 3,
    streak: 0,
    done: false,
})

const shuffledChoices = ref(questions.map((q) => shuffle(q.choices)))

const question  = computed(() => questions[state.current])
const progress  = computed(() => (state.current / questions.length) * 100)
const isCorrect = computed(() => state.checked && state.selected === question.value.answer)

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
    } else {
        state.lives--
        state.streak = 0
    }
}

function next() {
    if (state.lives <= 0) {
        state.done = true
        return
    }

    if (state.current < questions.length - 1) {
        state.current++
        state.selected = null
        state.checked = false
        return
    }

    if (props.nextExerciseId) {
        router.visit(route('exercise.show', props.nextExerciseId))
    } else {
        state.done = true
    }
}

function restart() {
    Object.assign(state, {
        current: 0, selected: null, checked: false,
        score: 0, lives: 3, streak: 0, done: false,
    })
    shuffledChoices.value = questions.map((q) => shuffle(q.choices))
}

const page = usePage()
const isAdmin = computed(() => page.props.auth.isAdmin)
</script>

<template>
    <div class="lesson">
        <button
            @click="toggleTheme"
            class="fixed top-4 right-4 z-50 rounded-md border border-gray-200 bg-white px-2 py-1 text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400"
            :title="theme === 'dark' ? 'Switch to light' : 'Switch to dark'"
        >{{ theme === 'dark' ? '☀️' : '🌙' }}</button>

        <!-- Back to profile -->
        <div class="mb-4">
            <Link
                :href="route('dashboard')"
                class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
            >← Back to Profile</Link>
        </div>

        <!-- Complete screen -->
        <div v-if="state.done || state.lives <= 0" class="complete">
            <div class="trophy">{{ state.lives > 0 ? '🏆' : '💔' }}</div>
            <h2>{{ state.lives > 0 ? 'Lesson complete!' : 'Out of lives!' }}</h2>
            <p v-if="state.lives > 0">You scored <strong>{{ state.score }} XP</strong></p>
            <p v-else>Better luck next time!</p>
            <button class="btn-check" @click="restart">Try again</button>
        </div>

        <!-- Active lesson -->
        <template v-else>

            <!-- Progress -->
            <div class="progress-bar">
                <div class="progress-fill" :style="{ width: progress + '%' }" />
            </div>

            <!-- Status row -->
            <div class="status-row">
                <div class="hearts">
                    <span v-for="i in 3" :key="i" :class="['heart', { lost: i > state.lives }]">❤️</span>
                </div>
                <span v-if="state.streak >= 2" class="streak">🔥 {{ state.streak }} streak</span>
                <span v-else />
                <span class="xp">{{ state.score }} XP</span>
            </div>

            <!-- Prompt -->
            <p class="label">Fill in the blank</p>
            <p class="question">{{ question.translation }}</p>

            <!-- Sentence with blank -->
            <div class="sentence-area">
                <template v-for="(word, i) in question.template" :key="i">
                    <!-- blank slot -->
                    <span v-if="i === question.blank" :class="['blank', { filled: state.selected }]">
            <span v-if="state.selected" class="blank-word" @click="removeSelected">
              {{ state.selected }}
            </span>
            <span v-else class="blank-placeholder">tap a word</span>
          </span>
                    <!-- regular word -->
                    <span v-else class="sentence-word">{{ word }}</span>
                </template>
            </div>

            <!-- Word choices -->
            <div class="choices">
                <button
                    v-for="word in shuffledChoices[state.current]"
                    :key="word"
                    class="choice-btn"
                    :disabled="state.selected === word || state.checked"
                    @click="selectChoice(word)"
                >
                    {{ word }}
                </button>
            </div>

            <!-- Feedback -->
            <div v-if="state.checked" :class="['feedback', isCorrect ? 'feedback--correct' : 'feedback--wrong']">
                <p class="feedback-title">{{ isCorrect ? '✓ Correct!' : '✗ Incorrect' }}</p>
                <p v-if="isWrong">Correct answer: <strong>{{ question.answer }}</strong></p>
            </div>

            <!-- Check / Continue button -->
            <button
                class="btn-check"
                :disabled="!state.selected && !state.checked"
                @click="state.checked ? next() : check()"
            >
                {{ state.checked ? 'Continue' : 'Check' }}
            </button>

            <button
                @click="showForm = !showForm"
                class="mt-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
            >
                {{ showForm ? 'Cancel' : 'Edit Exercise' }}
            </button>

            <UpdateExerciseForm
                v-if="showForm"
                :exercise="exercise"
                :exercise-types="exerciseTypes"
                @success="showForm = false"
                @cancel="showForm = false"
            />
        </template>
    </div>

</template>

<style scoped>
.lesson {
    max-width: 520px;
    margin: 0 auto;
    padding: 2rem 1rem;
    font-family: sans-serif;
}

/* Progress */
.progress-bar {
    height: 8px;
    background: #e5e5e5;
    border-radius: 99px;
    margin-bottom: 1.5rem;
    overflow: hidden;
}
.progress-fill {
    height: 100%;
    background: #58cc02;
    border-radius: 99px;
    transition: width 0.4s ease;
}

/* Status */
.status-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}
.hearts { display: flex; gap: 4px; }
.heart { font-size: 20px; transition: opacity 0.3s; }
.heart.lost { opacity: 0.2; filter: grayscale(1); }
.streak { font-size: 13px; font-weight: 700; color: #ff9600; }
.xp { font-size: 13px; font-weight: 600; color: #777; }

/* Prompt */
.label {
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: #aaa;
    margin-bottom: .4rem;
}
.question {
    font-size: 20px;
    font-weight: 600;
    color: #333;
    margin-bottom: 1.5rem;
    line-height: 1.4;
}

/* Sentence */
.sentence-area {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 6px;
    padding-bottom: 12px;
    border-bottom: 2px solid #e5e5e5;
    margin-bottom: 1.5rem;
    min-height: 52px;
}
.sentence-word { font-size: 18px; color: #333; }
.blank {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 88px;
    height: 36px;
    border-bottom: 3px solid #ddd;
    padding: 0 8px;
}
.blank.filled { border-bottom-color: #1cb0f6; }
.blank-word {
    font-size: 16px;
    font-weight: 600;
    color: #1cb0f6;
    cursor: pointer;
}
.blank-word:hover { opacity: 0.7; }
.blank-placeholder { font-size: 12px; color: #bbb; }

/* Choices */
.choices {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 1.5rem;
}
.choice-btn {
    padding: 10px 20px;
    border-radius: 12px;
    border: 2px solid #ddd;
    background: #fff;
    color: #333;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: transform 0.1s, border-color 0.15s;
}
.choice-btn:hover:not(:disabled) {
    border-color: #1cb0f6;
    transform: translateY(-2px);
}
.choice-btn:disabled { opacity: 0.35; cursor: default; }

/* Feedback */
.feedback {
    border-radius: 12px;
    padding: 12px 16px;
    margin-bottom: 1rem;
}
.feedback--correct { background: #d7ffb8; color: #3c7d00; }
.feedback--wrong   { background: #ffdfe0; color: #9e1c1c; }
.feedback-title    { font-weight: 700; font-size: 15px; margin-bottom: 4px; }

/* Check button */
.btn-check {
    width: 100%;
    padding: 14px;
    border-radius: 14px;
    border: none;
    background: #58cc02;
    color: #fff;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    transition: background 0.15s;
}
.btn-check:hover:not(:disabled) { background: #46a302; }
.btn-check:disabled { background: #e5e5e5; color: #aaa; cursor: default; }

/* Complete */
.complete { text-align: center; padding: 3rem 0; }
.trophy { font-size: 64px; margin-bottom: 1rem; }
.complete h2 { font-size: 26px; font-weight: 700; margin-bottom: .5rem; }
.complete p { color: #666; margin-bottom: 1.5rem; }
</style>

<style>
html.dark .lesson { color: #e8e8f0; }
html.dark .progress-bar { background: #374151; }
html.dark .label { color: #6b7280; }
html.dark .question { color: #f3f4f6; }
html.dark .sentence-area { border-bottom-color: #374151; }
html.dark .sentence-word { color: #e5e7eb; }
html.dark .blank { border-bottom-color: #4b5563; }
html.dark .blank.filled { border-bottom-color: #1cb0f6; }
html.dark .blank-placeholder { color: #6b7280; }
html.dark .xp { color: #9ca3af; }
html.dark .choice-btn { background: #1f2937; border-color: #374151; color: #e5e7eb; }
html.dark .choice-btn:hover:not(:disabled) { border-color: #1cb0f6; }
html.dark .btn-check:disabled { background: #374151; color: #6b7280; }
html.dark .complete h2 { color: #f3f4f6; }
html.dark .complete p { color: #9ca3af; }
</style>
