<script setup>
import '@/assets/scss/components/learning-path-card.scss'
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
    path: {
        type: Object,
        required: true,
    },
    showFinishedBadge: {
        type: Boolean,
        default: false,
    },
})

const progressPercent = computed(() => {
    const total = props.path.lessons_count ?? 0
    if (!total) return 0
    return Math.round(((props.path.completed_lessons_count ?? 0) / total) * 100)
})
</script>

<template>
    <article class="nb-path-card">
        <span v-if="showFinishedBadge && path.is_finished" class="nb-path-card__done">Finished</span>
        <div class="nb-path-card__head">
            <h3 class="nb-path-card__name">{{ path.name }}</h3>
            <span class="nb-path-card__lang">{{ path.language }}</span>
        </div>
        <div v-if="path.exercise_types?.length" class="nb-path-card__types">
            <span v-for="t in path.exercise_types" :key="t" class="nb-path-card__type">{{ t }}</span>
        </div>
        <div class="nb-path-card__track">
            <div class="nb-path-card__fill" :style="{ width: progressPercent + '%' }"></div>
        </div>
        <div class="nb-path-card__foot">
            <span class="nb-path-card__count">{{ path.completed_lessons_count ?? 0 }} / {{ path.lessons_count ?? 0 }} lessons</span>
            <div class="nb-path-card__actions">
                <Link
                    :href="route('learning-paths.show', path.id)"
                    class="nb-path-card__btn"
                >Show path</Link>
                <Link
                    v-if="path.continue_lesson_id"
                    :href="route('lesson.show', path.continue_lesson_id)"
                    class="nb-path-card__cta"
                >Continue <font-awesome-icon icon="arrow-right-long" /></Link>
            </div>
        </div>
    </article>
</template>
