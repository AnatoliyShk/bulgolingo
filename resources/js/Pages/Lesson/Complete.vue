<script setup>
import '@/assets/scss/components/exercise/player.scss'
import '@/assets/scss/components/lesson/complete.scss'
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { useTheme } from '@/composables/useTheme'
import ThemeToggle from '@/Components/ThemeToggle.vue'

const { theme } = useTheme()

const props = defineProps({
    lessonName: { type: String, required: true },
    nextExerciseId: { type: Number, default: null },
    learningPathId: { type: Number, default: null },
})

const continueHref = computed(() => {
    if (props.nextExerciseId) return route('exercise.show', props.nextExerciseId)
    if (props.learningPathId) return route('learning-paths.show', props.learningPathId)
    return route('dashboard')
})

const continueLabel = computed(() => {
    if (props.nextExerciseId) return 'Continue to next lesson'
    if (props.learningPathId) return 'Back to learning path'
    return 'Back to dashboard'
})
</script>

<template>
    <Head title="Lesson complete">
        <link
            href="https://fonts.bunny.net/css?family=unbounded:400,600,700,800,900|manrope:400,500,600,700,800&subset=cyrillic,latin&display=swap"
            rel="stylesheet"
        />
    </Head>

    <div class="nb-ex" :class="theme">
        <header class="nb-lesson-complete__bar">
            <ThemeToggle />
        </header>

        <main class="nb-lesson-complete">
            <p class="nb-lesson-complete__badge" aria-hidden="true">🎉</p>
            <h1 class="nb-lesson-complete__title">Lesson complete!</h1>
            <p class="nb-lesson-complete__lesson-name">You finished {{ lessonName }}.</p>

            <div class="nb-lesson-complete__actions">
                <Link class="nb-ex-action" :href="continueHref">
                    {{ continueLabel }}
                </Link>
            </div>
        </main>
    </div>
</template>
