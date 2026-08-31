<script setup>
import '@/assets/scss/components/learning-path/list.scss'
import { Head, Link } from '@inertiajs/vue3'
import { useTheme } from '@/composables/useTheme'
import TopBar from '@/Components/TopBar.vue'

const props = defineProps({
    title: { type: String, required: true },
    emptyMessage: { type: String, default: 'No learning paths here yet.' },
    paths: { type: Array, default: () => [] },
})

const { theme } = useTheme()

function progressPercent(path) {
    const total = path.lessons_count ?? 0
    if (!total) return 0
    return Math.round(((path.completed_lessons_count ?? 0) / total) * 100)
}
</script>

<template>
    <Head :title="title">
        <link
            href="https://fonts.bunny.net/css?family=unbounded:400,600,700,800,900|manrope:400,500,600,700,800&subset=cyrillic,latin&display=swap"
            rel="stylesheet"
        />
    </Head>

    <div class="nb-path-list" :class="theme">
        <TopBar />

        <main class="nb-path-list__main">
            <div class="nb-path-list__head">
                <span class="nb-path-list__badge">Пътища</span>
                <h1 class="nb-path-list__title">{{ title }}</h1>
            </div>

            <div v-if="paths.length" class="nb-path-list__paths">
                <article
                    v-for="(path, i) in paths"
                    :key="path.id"
                    class="nb-path-list__path"
                    :style="{ animationDelay: (i * 60) + 'ms' }"
                >
                    <span v-if="path.is_finished" class="nb-path-list__path-done">Finished</span>

                    <div class="nb-path-list__path-head">
                        <h3 class="nb-path-list__path-name">{{ path.name }}</h3>
                        <span class="nb-path-list__path-lang">{{ path.language }}</span>
                    </div>

                    <div v-if="path.exercise_types?.length" class="nb-path-list__path-types">
                        <span v-for="t in path.exercise_types" :key="t" class="nb-path-list__path-type">{{ t }}</span>
                    </div>

                    <div class="nb-path-list__path-track">
                        <div class="nb-path-list__path-fill" :style="{ width: progressPercent(path) + '%' }"></div>
                    </div>

                    <div class="nb-path-list__path-foot">
                        <span class="nb-path-list__path-count">{{ path.completed_lessons_count ?? 0 }} / {{ path.lessons_count ?? 0 }} lessons</span>
                        <div class="nb-path-list__path-actions">
                            <Link :href="route('learning-paths.show', path.id)" class="nb-path-list__path-btn">Show path</Link>
                            <Link
                                v-if="path.continue_lesson_id"
                                :href="route('lesson.show', path.continue_lesson_id)"
                                class="nb-path-list__path-cta"
                            >Continue <font-awesome-icon icon="arrow-right" /></Link>
                        </div>
                    </div>
                </article>
            </div>

            <div v-else class="nb-path-list__empty">
                <p class="nb-path-list__empty-title">{{ emptyMessage }}</p>
                <Link :href="route('learning-paths.index')" class="nb-path-list__empty-btn">Browse learning paths <font-awesome-icon icon="arrow-right" /></Link>
            </div>
        </main>
    </div>
</template>
