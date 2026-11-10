<script setup>
import { computed } from 'vue'
import ExerciseFeedback from '@/Components/ExerciseFeedback.vue'
import NextExerciseButton from '@/Components/NextExerciseButton.vue'
import { useChoice } from '@/composables/useChoice'

const props = defineProps({
    clause: { type: Object, required: true },
})

const emit = defineEmits(['complete'])

const answer    = computed(() => props.clause.correct_option ?? true)
const sentence  = computed(() => props.clause.sentence ?? '')
const explanation = computed(() => props.clause.explanation ?? '')

const { chosen, checked, isCorrect, select, retry } = useChoice(() => props.clause, answer)
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

        <ExerciseFeedback v-if="checked" :correct="isCorrect" :explanation="explanation" />

        <button
            v-if="checked && !isCorrect"
            class="nb-ex-action"
            @click="retry"
        >Try Again</button>

        <NextExerciseButton v-if="checked && isCorrect" @advance="emit('complete')" />
    </div>
</template>
