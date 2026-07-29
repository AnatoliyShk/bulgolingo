<!-- resources/js/Pages/Profile/Show.vue -->
<script setup>
import '@/assets/scss/components/profile/show.scss'
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { useTheme } from '@/composables/useTheme'
import { useForm } from '@inertiajs/vue3'

const logoutForm = useForm({})

const props = defineProps({
    user: Object,
    learningPaths: {
        type: Array,
        default: () => [],
    },
    appName: {
        type: String,
        default: 'Bulgolingo',
    },
})

const { theme, toggleTheme } = useTheme()

const initial = computed(() => {
    const name = props.user?.name?.trim() ?? ''
    return name ? Array.from(name)[0].toUpperCase() : '?'
})

const memberSince = computed(() => {
    if (!props.user?.created_at) return null
    return new Intl.DateTimeFormat('en', { month: 'long', year: 'numeric' }).format(new Date(props.user.created_at))
})

function progressPercent(path) {
    const total = path.lessons_count ?? 0
    if (!total) return 0
    return Math.round(((path.completed_lessons_count ?? 0) / total) * 100)
}
</script>

<template>
    <div class="nb-prof" :class="theme">

        <nav class="nb-prof__nav">
            <div class="nb-prof__nav-inner">
                <Link href="/" class="nb-prof__logo" aria-label="Back to home">
                    <span class="nb-prof__logo-mark">BB</span>
                    <span class="nb-prof__logo-text">{{ appName }}</span>
                </Link>
                <div class="nb-prof__nav-actions">
                    <button
                        class="nb-prof__theme-btn"
                        @click="toggleTheme"
                        :aria-label="theme === 'dark' ? 'Switch to light theme' : 'Switch to dark theme'"
                    >{{ theme === 'dark' ? '☀️' : '🌙' }}</button>
                    <button
                        class="nb-prof__logout-btn"
                        @click="logoutForm.post(route('logout'))"
                    >Log out</button>
                </div>
            </div>
        </nav>

        <main class="nb-prof__main">

            <!-- Identity -->
            <section class="nb-prof__hero">
                <div class="nb-prof__avatar">
                    <span class="nb-prof__avatar-letter">{{ initial }}</span>
                </div>
                <div class="nb-prof__info">
                    <div class="nb-prof__eyebrow">
                        Профил <span class="nb-prof__eyebrow-en">· profile</span>
                    </div>
                    <h1 class="nb-prof__name">{{ user.name }}</h1>
                    <p class="nb-prof__email">{{ user.email }}</p>
                    <p v-if="memberSince" class="nb-prof__member">Learning Bulgarian since {{ memberSince }}</p>
                </div>
                <Link :href="route('profile.edit')" class="nb-prof__edit-btn" aria-label="Edit profile">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </Link>
            </section>

            <div class="nb-prof__seam" aria-hidden="true"></div>

            <!-- Learning paths -->
            <section class="nb-prof__section">
                <div class="nb-prof__section-head">
                    <h2 class="nb-prof__section-title">Пътят</h2>
                    <span class="nb-prof__section-badge">your path</span>
                </div>

                <div v-if="learningPaths.length" class="nb-prof__paths">
                    <article
                        v-for="path in learningPaths"
                        :key="path.id"
                        class="nb-prof__path"
                    >
                        <div class="nb-prof__path-head">
                            <h3 class="nb-prof__path-name">{{ path.name }}</h3>
                            <span class="nb-prof__path-lang">{{ path.language }}</span>
                        </div>
                        <div v-if="path.exercise_types?.length" class="nb-prof__path-types">
                            <span v-for="t in path.exercise_types" :key="t" class="nb-prof__path-type">{{ t }}</span>
                        </div>
                        <div class="nb-prof__path-track">
                            <div class="nb-prof__path-fill" :style="{ width: progressPercent(path) + '%' }"></div>
                        </div>
                        <div class="nb-prof__path-foot">
                            <span class="nb-prof__path-count">{{ path.completed_lessons_count ?? 0 }} / {{ path.lessons_count ?? 0 }} lessons</span>
                            <div class="nb-prof__path-actions">
                                <Link
                                    :href="route('learning-paths.show', path.id)"
                                    class="nb-prof__path-btn"
                                >Show path</Link>
                                <Link
                                    v-if="path.continue_lesson_id"
                                    :href="route('lesson.show', path.continue_lesson_id)"
                                    class="nb-prof__path-cta"
                                >Continue <font-awesome-icon icon="arrow-right" /></Link>
                            </div>
                        </div>
                    </article>
                </div>

                <div v-else class="nb-prof__empty">
                    <p class="nb-prof__empty-title">Няма избран път</p>
                    <p class="nb-prof__empty-sub">You haven't picked a learning path yet.</p>
                    <Link :href="route('learning-paths.index')" class="nb-prof__empty-btn">Choose a path <font-awesome-icon icon="arrow-right" /></Link>
                </div>
            </section>

            <div class="nb-prof__seam" aria-hidden="true"></div>

            <!-- Stats link -->
            <Link :href="route('stats.show')" class="nb-prof__stats">
                <div class="nb-prof__stats-label">
                    <span class="nb-prof__stats-bg">Статистика</span>
                    <span class="nb-prof__stats-en">view your stats</span>
                </div>
                <span class="nb-prof__stats-arrow" aria-hidden="true"><font-awesome-icon icon="arrow-right" /></span>
            </Link>

        </main>
    </div>
</template>
