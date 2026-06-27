<script setup>
import '@/assets/scss/components/exercise/player.scss'
import { ref, computed, watch } from 'vue'
import { Head, usePage, router, Link } from '@inertiajs/vue3'
import { useTheme } from '@/composables/useTheme'
import UpdateExerciseForm from '@/Components/Forms/UpdateExerciseForm.vue'
import FillInTheBlank from './FillInTheBlank.vue'
import ImageMatching from './ImageMatching.vue'
import MultipleChoice from './MultipleChoice.vue'
import TrueFalse from './TrueFalse.vue'

const { theme, toggleTheme } = useTheme()

const props = defineProps({
    exercise:       { type: Object, required: true },
    exerciseTypes:  { type: Array,  required: true },
    totalExercises: { type: Number, default: 0 },
    completedCount: { type: Number, default: 0 },
})

const page    = usePage()
const isAdmin = computed(() => page.props.auth.isAdmin)

const showForm = ref(false)
watch(() => props.exercise.id, () => { showForm.value = false })

const progressPct = computed(() =>
    props.totalExercises > 0 ? (props.completedCount / props.totalExercises) * 100 : 0
)
const remaining = computed(() => props.totalExercises - props.completedCount)

const typeLabel = computed(() => {
    const match = props.exerciseTypes.find(t => t.value === props.exercise.decision_type)
    return match?.label ?? props.exercise.decision_type ?? ''
})

const exerciseComponent = computed(() => {
    switch (props.exercise.decision_type) {
        case 'fill_in_the_blank': return FillInTheBlank
        case 'image_matching':    return ImageMatching
        case 'multiple_choice':   return MultipleChoice
        case 'true_false':        return TrueFalse
        default:                  return null
    }
})

const exerciseImageUrl = computed(() => props.exercise.images?.[0]?.url ?? null)

function onComplete() {
    router.post(route('exercise.complete', props.exercise.id))
}
</script>

<template>
    <Head>
        <link
            href="https://fonts.bunny.net/css?family=unbounded:400,600,700,800,900|manrope:400,500,600,700,800&subset=cyrillic,latin&display=swap"
            rel="stylesheet"
        />
    </Head>

    <div class="nb-ex" :class="theme">
        <header class="nb-ex__bar">
            <Link :href="route('dashboard')" class="nb-ex__back" aria-label="Back to dashboard">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
                </svg>
            </Link>

            <div class="nb-ex__progress">
                <div class="nb-ex__track">
                    <div class="nb-ex__fill" :style="{ width: progressPct + '%' }" />
                </div>
                <span class="nb-ex__count">{{ completedCount }} / {{ totalExercises }}</span>
            </div>

            <button class="nb-ex__toggle" @click="toggleTheme" :title="theme === 'dark' ? 'Switch to light' : 'Switch to dark'">
                {{ theme === 'dark' ? '☀' : '☾' }}
            </button>
        </header>

        <main class="nb-ex__sheet">
            <p class="nb-ex__eyebrow">
                <span class="nb-ex__badge" lang="bg">Упражнение</span>
                <span class="nb-ex__meta">{{ typeLabel }} · {{ remaining }} remaining</span>
            </p>

            <div class="nb-ex__card">
                <component
                    v-if="exerciseComponent"
                    :is="exerciseComponent"
                    :clause="exercise.clause"
                    :image-url="exerciseImageUrl"
                    @complete="onComplete"
                />

                <div v-else class="nb-ex__unsupported">
                    Exercise type <strong>{{ typeLabel }}</strong> is not yet supported in the player.
                </div>
            </div>

            <div v-if="isAdmin" class="nb-ex__edit">
                <button class="nb-ex__edit-btn" @click="showForm = !showForm">
                    {{ showForm ? 'Cancel' : 'Edit Exercise' }}
                </button>
                <UpdateExerciseForm
                    v-if="showForm"
                    :exercise="exercise"
                    :exercise-types="exerciseTypes"
                    :submit-route="route('admin.exercises.update', exercise.id)"
                    @success="showForm = false"
                    @cancel="showForm = false"
                />
            </div>
        </main>
    </div>
</template>
