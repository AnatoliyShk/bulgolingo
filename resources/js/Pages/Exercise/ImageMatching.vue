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
        <div class="nb-ex-image">
            <img
                v-if="imageUrl"
                :src="imageUrl"
                alt="Exercise image"
                class="nb-ex-image__img"
            />
            <div v-else class="nb-ex-image__placeholder">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M4 16l4-4a3 3 0 014 0l4 4m0 0l1-1a3 3 0 014 0l3 3M4 20h16" />
                </svg>
                <span>No image</span>
            </div>
        </div>

        <p class="nb-ex-prompt">Which word matches the image?</p>

        <div class="nb-ex-grid">
            <button
                v-for="(option, idx) in options"
                :key="idx"
                class="nb-ex-opt"
                :class="{
                    'nb-ex-opt--correct': checked && idx === correctIdx && isCorrect && chosen === idx,
                    'nb-ex-opt--wrong':   checked && chosen === idx && !isCorrect,
                    'nb-ex-opt--reveal':  checked && idx === correctIdx && !isCorrect,
                    'nb-ex-opt--dim':     checked && chosen !== idx && idx !== correctIdx,
                }"
                :disabled="checked"
                @click="select(idx)"
            >
                {{ option }}
            </button>
        </div>

        <div v-if="checked && !isCorrect" class="nb-ex-feedback nb-ex-feedback--wrong">
            <p class="nb-ex-feedback__title">✗ Incorrect</p>
            <p v-if="explanation" class="nb-ex-feedback__body">{{ explanation }}</p>
        </div>

        <div v-if="checked && isCorrect" class="nb-ex-feedback nb-ex-feedback--correct">
            <p class="nb-ex-feedback__title">✓ Correct!</p>
            <p v-if="explanation" class="nb-ex-feedback__body">{{ explanation }}</p>
        </div>

        <button v-if="checked && !isCorrect" class="nb-ex-action" @click="retry">Try Again</button>
        <button v-if="checked && isCorrect"  class="nb-ex-action" @click="emit('complete')">Next Exercise</button>
    </div>
</template>
