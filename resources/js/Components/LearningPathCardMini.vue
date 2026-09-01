<script setup>
import '@/assets/scss/components/learning-path-card-mini.scss'
import { computed } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'

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

const EXERCISE_TYPE_META = {
    multiple_choice: { icon: 'list-check', label: 'Multiple choice' },
    true_false: { icon: 'check-double', label: 'True or false' },
    fill_in_the_blank: { icon: 'pen-to-square', label: 'Fill in the blank' },
    image_matching: { icon: 'image', label: 'Image matching' },
}

function typeMeta(type) {
    return EXERCISE_TYPE_META[type]
}

const page = usePage()
const isAuthenticated = computed(() => !!page.props.auth?.user)

// Only an enrolled path carries lesson counts — the plain catalog entry
// doesn't, and that's what tells "not started yet" apart from "finished"
// (continue_lesson_id is null in both cases).
const isEnrolled = computed(() => props.path.lessons_count !== undefined)

function start() {
    router.post(route('learning-paths.start', props.path.id))
}
</script>

<template>
    <article class="nb-path-card-mini">
        <span v-if="showFinishedBadge && path.is_finished" class="nb-path-card-mini__done">Finished</span>

        <span class="nb-path-card-mini__tag">{{ path.language }}</span>
        <h3 class="nb-path-card-mini__name">{{ path.name }}</h3>

        <ul v-if="path.exercise_types?.length" class="nb-path-card-mini__types" aria-label="Exercise types in this path">
            <template v-for="type in path.exercise_types" :key="type">
                <li v-if="typeMeta(type)" class="nb-path-card-mini__type" :title="typeMeta(type).label">
                    <font-awesome-icon :icon="typeMeta(type).icon" />
                </li>
            </template>
        </ul>

        <span v-if="isEnrolled" class="nb-path-card-mini__count">
            {{ path.completed_lessons_count ?? 0 }} / {{ path.lessons_count ?? 0 }} lessons
        </span>

        <Link
            v-if="path.continue_lesson_id"
            :href="route('lesson.show', path.continue_lesson_id)"
            class="nb-path-card-mini__btn"
        >Continue <font-awesome-icon icon="arrow-right-long" /></Link>
        <Link
            v-else-if="isEnrolled"
            :href="route('learning-paths.show', path.id)"
            class="nb-path-card-mini__btn"
        >Show path</Link>
        <button
            v-else-if="isAuthenticated"
            type="button"
            class="nb-path-card-mini__btn"
            @click="start"
        >Start</button>
        <Link v-else href="/register" class="nb-path-card-mini__btn">Sign up to start</Link>
    </article>
</template>
