<script setup>
import { ref, reactive, computed, watch } from 'vue'

// clause.pairs = [[english, bulgarian], ...], clause.explanation = prompt
// Left column shows pair[0] (English), right column shows pair[1] (Bulgarian), both shuffled.
// User taps one from each side; the pair indices must match to be correct.

const props = defineProps({
    clause: { type: Object, required: true },
})

const emit = defineEmits(['complete'])

const shuffle = arr => [...arr].sort(() => Math.random() - 0.5)

function buildItems(clause) {
    const pairs = clause.pairs ?? []
    console.log(pairs)
    return {
        left:  shuffle(pairs.map((pair, i) => ({ text: pair[0], idx: i }))),
        right: shuffle(pairs.map((pair, i) => ({ text: pair[1], idx: i }))),
    }
}

const items = ref(buildItems(props.clause))

const state = reactive({
    selectedLeft:  null,   // original pair idx selected on the left
    selectedRight: null,   // original pair idx selected on the right
    matched:       [],     // array of matched pair indices (array for Vue reactivity)
    wrongLeft:     null,
    wrongRight:    null,
})

function resetState() {
    state.selectedLeft  = null
    state.selectedRight = null
    state.matched       = []
    state.wrongLeft     = null
    state.wrongRight    = null
}

watch(() => props.clause, () => {
    items.value = buildItems(props.clause)
    resetState()
}, { deep: true })

const total  = computed(() => (props.clause.pairs ?? []).length)
const prompt = computed(() => props.clause.explanation ?? '')

function isMatched(idx)    { return state.matched.includes(idx) }
function isWrongLeft(idx)  { return state.wrongLeft === idx }
function isWrongRight(idx) { return state.wrongRight === idx }

function selectLeft(idx) {
    if (isMatched(idx) || state.wrongLeft !== null) return
    state.selectedLeft = state.selectedLeft === idx ? null : idx
    tryMatch()
}

function selectRight(idx) {
    if (isMatched(idx) || state.wrongRight !== null) return
    state.selectedRight = state.selectedRight === idx ? null : idx
    tryMatch()
}

function tryMatch() {
    if (state.selectedLeft === null || state.selectedRight === null) return

    if (state.selectedLeft === state.selectedRight) {
        // Correct pair
        state.matched.push(state.selectedLeft)
        state.selectedLeft  = null
        state.selectedRight = null
        if (state.matched.length === total.value) emit('complete')
    } else {
        // Wrong pair — flash and reset
        state.wrongLeft  = state.selectedLeft
        state.wrongRight = state.selectedRight
        state.selectedLeft  = null
        state.selectedRight = null
        setTimeout(() => {
            state.wrongLeft  = null
            state.wrongRight = null
        }, 700)
    }
}
</script>

<template>
    <div>
        <p class="prompt">{{ prompt }}</p>

        <div class="columns">
            <div class="col">
                <button
                    v-for="item in items.left"
                    :key="item.idx"
                    class="pair-btn"
                    :class="{
                        'pair-btn--selected': state.selectedLeft === item.idx && !isMatched(item.idx),
                        'pair-btn--matched':  isMatched(item.idx),
                        'pair-btn--wrong':    isWrongLeft(item.idx),
                    }"
                    :disabled="isMatched(item.idx)"
                    @click="selectLeft(item.idx)"
                >{{ item.text }}</button>
            </div>

            <div class="col">
                <button
                    v-for="item in items.right"
                    :key="item.idx"
                    class="pair-btn"
                    :class="{
                        'pair-btn--selected': state.selectedRight === item.idx && !isMatched(item.idx),
                        'pair-btn--matched':  isMatched(item.idx),
                        'pair-btn--wrong':    isWrongRight(item.idx),
                    }"
                    :disabled="isMatched(item.idx)"
                    @click="selectRight(item.idx)"
                >{{ item.text }}</button>
            </div>
        </div>

        <p class="tally">{{ state.matched.length }} / {{ total }} matched</p>
    </div>
</template>

<style scoped>
.prompt {
    font-family: 'PT Serif', serif;
    font-size: 1.5rem;
    font-weight: 700;
    line-height: 1.35;
    color: var(--ink);
    margin: 0 0 2rem;
}

.columns {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: .75rem;
    margin-bottom: 1.5rem;
}

.col {
    display: flex;
    flex-direction: column;
    gap: .65rem;
}

.pair-btn {
    width: 100%;
    padding: .75rem 1rem;
    border-radius: .65rem;
    border: 1.5px solid var(--border);
    background: var(--surface);
    color: var(--ink);
    font-family: 'PT Sans', sans-serif;
    font-size: .95rem;
    font-weight: 600;
    text-align: center;
    cursor: pointer;
    transition: border-color .15s ease, background .15s ease, color .15s ease, transform .1s ease;
}

.pair-btn:hover:not(:disabled) {
    border-color: var(--rose);
    color: var(--rose);
    transform: translateY(-2px);
}

.pair-btn--selected {
    border-color: var(--gold);
    background: color-mix(in srgb, var(--gold) 8%, transparent);
    color: var(--gold);
}

.pair-btn--matched {
    border-color: var(--forest);
    background: var(--forest-bg);
    color: var(--forest);
    opacity: .7;
    cursor: default;
    transform: none;
}

.pair-btn--wrong {
    border-color: var(--rose);
    background: var(--rose-bg);
    color: var(--rose);
    animation: shake .35s ease;
}

.tally {
    font-size: .8rem;
    font-weight: 700;
    letter-spacing: .06em;
    text-transform: uppercase;
    color: var(--muted);
    text-align: center;
    margin: 0;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25%       { transform: translateX(-5px); }
    75%       { transform: translateX(5px); }
}

.pair-btn:focus-visible {
    outline: 2px solid var(--rose);
    outline-offset: 2px;
}
</style>
