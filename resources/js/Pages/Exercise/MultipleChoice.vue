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
        <p class="nb-ex-prompt">{{ prompt }}</p>

        <div class="nb-ex-columns">
            <div class="nb-ex-col">
                <button
                    v-for="item in items.left"
                    :key="item.idx"
                    class="nb-ex-opt"
                    :class="{
                        'nb-ex-opt--selected': state.selectedLeft === item.idx && !isMatched(item.idx),
                        'nb-ex-opt--matched':  isMatched(item.idx),
                        'nb-ex-opt--wrong':    isWrongLeft(item.idx),
                    }"
                    :disabled="isMatched(item.idx)"
                    @click="selectLeft(item.idx)"
                >{{ item.text }}</button>
            </div>

            <div class="nb-ex-col">
                <button
                    v-for="item in items.right"
                    :key="item.idx"
                    class="nb-ex-opt"
                    :class="{
                        'nb-ex-opt--selected': state.selectedRight === item.idx && !isMatched(item.idx),
                        'nb-ex-opt--matched':  isMatched(item.idx),
                        'nb-ex-opt--wrong':    isWrongRight(item.idx),
                    }"
                    :disabled="isMatched(item.idx)"
                    @click="selectRight(item.idx)"
                >{{ item.text }}</button>
            </div>
        </div>

        <p class="nb-ex-tally">{{ state.matched.length }} / {{ total }} matched</p>
    </div>
</template>
