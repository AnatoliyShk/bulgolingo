<script setup>
import { ref, computed, watch } from 'vue'
import NextExerciseButton from '@/Components/NextExerciseButton.vue'

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
}

function retry() {
    chosen.value  = null
    checked.value = false
}
</script>

<template>
    <div>
        <p class="nb-ex-prompt">{{ sentence }}</p>

        <div class="nb-ex-tf">
            <button
                class="nb-ex-opt nb-ex-opt--tf"
                :class="{
                    'nb-ex-opt--correct': checked && chosen === true  &&  isCorrect,
                    'nb-ex-opt--wrong':   checked && chosen === true  && !isCorrect,
                    'nb-ex-opt--dim':     checked && chosen !== true,
                }"
                :disabled="checked"
                @click="select(true)"
            >
                <span class="nb-ex-tf__icon">✓</span>
                <span class="nb-ex-tf__label">True</span>
            </button>

            <button
                class="nb-ex-opt nb-ex-opt--tf"
                :class="{
                    'nb-ex-opt--correct': checked && chosen === false &&  isCorrect,
                    'nb-ex-opt--wrong':   checked && chosen === false && !isCorrect,
                    'nb-ex-opt--dim':     checked && chosen !== false,
                }"
                :disabled="checked"
                @click="select(false)"
            >
                <span class="nb-ex-tf__icon">✗</span>
                <span class="nb-ex-tf__label">False</span>
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

        <button
            v-if="checked && !isCorrect"
            class="nb-ex-action"
            @click="retry"
        >Try Again</button>

        <NextExerciseButton v-if="checked && isCorrect" @advance="emit('complete')" />
    </div>
</template>
