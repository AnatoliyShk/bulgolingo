<script setup>
import { ref, computed, watch } from 'vue'

const props = defineProps({
    clause: { type: Object, required: true },
})

const emit = defineEmits(['complete'])

const answer    = computed(() => props.clause.correct_option ?? true)
const sentence  = computed(() => props.clause.sentence ?? '')
const explanation = computed(() => props.clause.explanation ?? '')

const chosen  = ref(null)   // true | false | null
const checked = ref(false)

watch(() => props.clause, () => {
    chosen.value  = null
    checked.value = false
}, { deep: true })

const isCorrect = computed(() => checked.value && chosen.value === answer.value)

function select(value) {
    if (checked.value) return
    chosen.value  = value
    checked.value = true
    if (chosen.value === answer.value) emit('complete')
}

function retry() {
    chosen.value  = null
    checked.value = false
}
</script>

<template>
    <div>
        <p class="sentence">{{ sentence }}</p>

        <div class="choices">
            <button
                class="choice choice--true"
                :class="{
                    'choice--correct': checked && chosen === true  &&  isCorrect,
                    'choice--wrong':   checked && chosen === true  && !isCorrect,
                    'choice--dim':     checked && chosen !== true,
                }"
                :disabled="checked"
                @click="select(true)"
            >
                <span class="choice__icon">✓</span>
                <span class="choice__label">True</span>
            </button>

            <button
                class="choice choice--false"
                :class="{
                    'choice--correct': checked && chosen === false &&  isCorrect,
                    'choice--wrong':   checked && chosen === false && !isCorrect,
                    'choice--dim':     checked && chosen !== false,
                }"
                :disabled="checked"
                @click="select(false)"
            >
                <span class="choice__icon">✗</span>
                <span class="choice__label">False</span>
            </button>
        </div>

        <div v-if="checked && !isCorrect" class="feedback feedback--wrong">
            <p class="feedback__title">✗ Incorrect</p>
            <p v-if="explanation" class="feedback__body">{{ explanation }}</p>
        </div>

        <div v-if="checked && isCorrect" class="feedback feedback--correct">
            <p class="feedback__title">✓ Correct!</p>
            <p v-if="explanation" class="feedback__body">{{ explanation }}</p>
        </div>

        <button
            v-if="checked && !isCorrect"
            class="btn-action"
            @click="retry"
        >Try Again</button>

        <button
            v-if="checked && isCorrect"
            class="btn-action"
            @click="emit('complete')"
        >Next Exercise</button>
    </div>
</template>

<style scoped>
.sentence {
    font-family: 'PT Serif', serif;
    font-size: 1.5rem;
    font-weight: 700;
    line-height: 1.35;
    color: var(--ink);
    margin: 0 0 2.5rem;
}

.choices {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1.75rem;
}

.choice {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: .5rem;
    padding: .5rem 1rem;
    border-radius: .85rem;
    border: 1.5px solid var(--border);
    background: var(--surface);
    color: var(--ink);
    font-family: 'PT Sans', sans-serif;
    cursor: pointer;
    transition: border-color .15s ease, background .15s ease, color .15s ease, transform .12s ease;
}

.choice:hover:not(:disabled) {
    transform: translateY(-3px);
}

.choice--true:hover:not(:disabled)  { border-color: var(--forest); color: var(--forest); }
.choice--false:hover:not(:disabled) { border-color: var(--rose);   color: var(--rose); }

.choice__icon {
    font-size: 2rem;
    line-height: 1;
}

.choice__label {
    font-size: .85rem;
    font-weight: 700;
    letter-spacing: .05em;
    text-transform: uppercase;
}

.choice--correct {
    border-color: var(--forest);
    background: var(--forest-bg);
    color: var(--forest);
}

.choice--wrong {
    border-color: var(--rose);
    background: var(--rose-bg);
    color: var(--rose);
    animation: shake .35s ease;
}

.choice--dim {
    opacity: .3;
    cursor: default;
}

.feedback {
    border-radius: .75rem;
    padding: .85rem 1.1rem;
    margin-bottom: 1.25rem;
    border: 1px solid transparent;
}

.feedback--wrong   { background: var(--rose-bg);   border-color: var(--rose);   color: var(--rose); }
.feedback--correct { background: var(--forest-bg); border-color: var(--forest); color: var(--forest); }

.feedback__title { font-weight: 700; font-size: .9rem; margin: 0 0 .25rem; }
.feedback__body  { font-size: .85rem; margin: 0; opacity: .85; }

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

.btn-action:hover { opacity: .9; transform: translateY(-2px); }

.btn-action:focus-visible,
.choice:focus-visible {
    outline: 2px solid var(--rose);
    outline-offset: 2px;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25%       { transform: translateX(-5px); }
    75%       { transform: translateX(5px); }
}
</style>
