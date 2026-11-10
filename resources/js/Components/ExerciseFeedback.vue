<script setup>
import '@/assets/scss/components/exercise/player.scss'

defineProps({
    correct:     { type: Boolean, required: true },
    explanation: { type: String, default: '' },
    wrongTitle:  { type: String, default: '✗ Incorrect' },
})
</script>

<template>
    <div class="nb-ex-feedback" :class="correct ? 'nb-ex-feedback--correct' : 'nb-ex-feedback--wrong'">
        <p class="nb-ex-feedback__title">{{ correct ? '✓ Correct!' : wrongTitle }}</p>
        <slot />
        <p v-if="explanation" class="nb-ex-feedback__body">{{ explanation }}</p>
    </div>
</template>
