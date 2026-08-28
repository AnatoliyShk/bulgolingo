<script setup>
import '@/assets/scss/components/next-exercise-button.scss';
import { onBeforeUnmount, onMounted } from 'vue';
import { useTheme } from '@/composables/useTheme';

const props = defineProps({
    // How long the learner gets to read the feedback before the player moves
    // on by itself. The line above the button drains over the same span.
    duration: { type: Number, default: 5000 },
    label: { type: String, default: 'Next Exercise' },
});

const emit = defineEmits(['advance']);

const { theme } = useTheme();

let timer = null;
let advanced = false;

// Moves the player on, whether the countdown ran out or the learner clicked
// ahead of it. Guarded so a click in the last moments before the timer fires
// cannot advance the exercise twice.
function advance() {
    if (advanced) return;

    advanced = true;

    if (timer !== null) {
        clearTimeout(timer);
        timer = null;
    }

    emit('advance');
}

// The timeout, not the line's animation, is what actually advances: a viewer
// with reduced motion or a background tab that stalls the animation still
// gets moved on.
onMounted(() => {
    timer = setTimeout(advance, props.duration);
});

onBeforeUnmount(() => {
    if (timer !== null) clearTimeout(timer);
});
</script>

<template>
    <div class="nb-next" :class="`nb-next--${theme}`">
        <div class="nb-next__timer" aria-hidden="true">
            <div
                class="nb-next__timer-fill"
                :style="{ animationDuration: `${duration}ms` }"
            ></div>
        </div>

        <button type="button" class="nb-ex-action nb-next__btn" @click="advance">
            {{ label }}
        </button>
    </div>
</template>
