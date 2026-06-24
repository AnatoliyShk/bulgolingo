<script setup>
import { ref, computed, watch } from 'vue'

const props = defineProps({
    clause:   { type: Object, required: true },
    imageUrl: { type: String, default: null },
})

const emit = defineEmits(['complete'])

const options     = computed(() => props.clause.options ?? [])
const correctIdx  = computed(() => props.clause.correct_option ?? 0)
const explanation = computed(() => props.clause.explanation ?? '')

const chosen  = ref(null)
const checked = ref(false)

watch(() => props.clause, () => {
    chosen.value  = null
    checked.value = false
}, { deep: true })

const isCorrect = computed(() => checked.value && chosen.value === correctIdx.value)

function select(idx) {
    if (checked.value) return
    chosen.value  = idx
    checked.value = true
}

function retry() {
    chosen.value  = null
    checked.value = false
}
</script>

<template>
    <div>
        <div class="image-wrap">
            <img
                v-if="imageUrl"
                :src="imageUrl"
                alt="Exercise image"
                class="exercise-image"
            />
            <div v-else class="image-placeholder">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M4 16l4-4a3 3 0 014 0l4 4m0 0l1-1a3 3 0 014 0l3 3M4 20h16" />
                </svg>
                <span>No image</span>
            </div>
        </div>

        <p class="prompt">Which word matches the image?</p>

        <div class="options">
            <button
                v-for="(option, idx) in options"
                :key="idx"
                class="option-btn"
                :class="{
                    'option-btn--correct': checked && idx === correctIdx && isCorrect && chosen === idx,
                    'option-btn--wrong':   checked && chosen === idx && !isCorrect,
                    'option-btn--reveal':  checked && idx === correctIdx && !isCorrect,
                    'option-btn--dim':     checked && chosen !== idx && idx !== correctIdx,
                }"
                :disabled="checked"
                @click="select(idx)"
            >
                {{ option }}
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

        <button v-if="checked && !isCorrect" class="btn-action" @click="retry">Try Again</button>
        <button v-if="checked && isCorrect"  class="btn-action" @click="emit('complete')">Next Exercise</button>
    </div>
</template>

<style scoped>
.image-wrap {
    margin-bottom: 1.75rem;
    border-radius: 1rem;
    overflow: hidden;
    border: 1px solid var(--border);
    background: var(--surface);
}

.exercise-image {
    width: 100%;
    max-height: 18rem;
    object-fit: cover;
    display: block;
}

.image-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: .5rem;
    height: 10rem;
    color: var(--muted);
    font-size: .85rem;
}

.image-placeholder svg {
    width: 2.5rem;
    height: 2.5rem;
    opacity: .5;
}

.prompt {
    font-family: 'PT Serif', serif;
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--ink);
    margin: 0 0 1.25rem;
}

.options {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: .75rem;
    margin-bottom: 1.5rem;
}

.option-btn {
    padding: .85rem 1rem;
    border-radius: .75rem;
    border: 1.5px solid var(--border);
    background: var(--surface);
    color: var(--ink);
    font-family: 'PT Sans', sans-serif;
    font-size: .95rem;
    font-weight: 600;
    text-align: center;
    cursor: pointer;
    transition: border-color .15s ease, background .15s ease, color .15s ease, transform .12s ease;
}

.option-btn:hover:not(:disabled) {
    border-color: var(--rose);
    color: var(--rose);
    transform: translateY(-2px);
}

.option-btn--correct {
    border-color: var(--forest);
    background: var(--forest-bg);
    color: var(--forest);
}

.option-btn--wrong {
    border-color: var(--rose);
    background: var(--rose-bg);
    color: var(--rose);
    animation: shake .35s ease;
}

.option-btn--reveal {
    border-color: var(--forest);
    background: var(--forest-bg);
    color: var(--forest);
    opacity: .6;
}

.option-btn--dim {
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

.option-btn:focus-visible,
.btn-action:focus-visible {
    outline: 2px solid var(--rose);
    outline-offset: 2px;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25%       { transform: translateX(-5px); }
    75%       { transform: translateX(5px); }
}
</style>
