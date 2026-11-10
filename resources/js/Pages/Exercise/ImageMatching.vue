<script setup>
import { computed } from 'vue'
import ExerciseFeedback from '@/Components/ExerciseFeedback.vue'
import NextExerciseButton from '@/Components/NextExerciseButton.vue'
import { useChoice } from '@/composables/useChoice'

const props = defineProps({
    clause:   { type: Object, required: true },
    imageUrl: { type: String, default: null },
})

const emit = defineEmits(['complete'])

const options     = computed(() => props.clause.options ?? [])
const correctIdx  = computed(() => props.clause.correct_option ?? 0)
const explanation = computed(() => props.clause.explanation ?? '')

const { chosen, checked, isCorrect, select, retry } = useChoice(() => props.clause, correctIdx)
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

        <p class="nb-ex-prompt">Which sentence matches the image?</p>

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

        <ExerciseFeedback v-if="checked" :correct="isCorrect" :explanation="explanation" />

        <button v-if="checked && !isCorrect" class="nb-ex-action" @click="retry">Try Again</button>
        <NextExerciseButton v-if="checked && isCorrect" @advance="emit('complete')" />
    </div>
</template>
