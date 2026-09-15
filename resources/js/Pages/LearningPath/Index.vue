<script setup>
import '@/assets/scss/components/learning-path/index.scss'
import { computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import { useTheme } from '@/composables/useTheme'
import TopBar from '@/Components/TopBar.vue'
import LearningPathCardMini from '@/Components/LearningPathCardMini.vue'
import LearningPathSearch from '@/Components/LearningPathSearch.vue'
import LearningPathLevelFilter from '@/Components/LearningPathLevelFilter.vue'
import LearningPathSortFilter from '@/Components/LearningPathSortFilter.vue'

const props = defineProps({
    paths: Array,
    unfinishedPaths: { type: Array, default: () => [] },
    finishedPaths: { type: Array, default: () => [] },
    search: { type: Object, default: () => ({ enabled: false, query: '', unavailable: false }) },
    filters: { type: Object, default: () => ({ level: 'A2', sort: 'exercises_desc' }) },
})

const { theme } = useTheme()

const hasEnrolled = computed(() => props.unfinishedPaths.length > 0 || props.finishedPaths.length > 0)
const hasAny = computed(() => hasEnrolled.value || (props.paths?.length ?? 0) > 0)

// A level always narrows the page now — there being no "every level" choice
// — so an empty catalog section always means nothing at this level matched,
// never that nothing exists to show. A search says so in its own status
// line, so the level message is held back while one is in effect. The sort
// only orders the page — it never empties it, so it plays no part here.
const isSearching = computed(() => !!props.search.query && !props.search.unavailable)
const showCatalog = computed(() => (props.paths?.length ?? 0) > 0)
const levelIsEmpty = computed(() => !isSearching.value && !hasAny.value)
</script>

<template>
    <Head title="Learning paths">
        <link
            href="https://fonts.bunny.net/css?family=unbounded:400,600,700,800,900|manrope:400,500,600,700,800&subset=cyrillic,latin&display=swap"
            rel="stylesheet"
        />
    </Head>

    <div class="nb-paths" :class="theme">
        <TopBar />

        <main class="nb-paths__main">
            <div class="nb-paths__head">
                <span class="nb-paths__badge">Пътища</span>
                <h1 class="nb-paths__title">All learning paths</h1>
                <p class="nb-paths__sub">Pick a language path and start training today.</p>
                <LearningPathSearch v-if="search.enabled" />
                <LearningPathLevelFilter />
                <LearningPathSortFilter />
            </div>

            <p v-if="levelIsEmpty" class="nb-paths__empty">No learning paths at level {{ filters.level }} yet.</p>

            <section v-if="unfinishedPaths.length" class="nb-paths__section">
                <h2 class="nb-paths__section-title">In progress</h2>
                <div class="nb-paths__section-list">
                    <div
                        v-for="(path, i) in unfinishedPaths"
                        :key="path.id"
                        class="nb-paths__section-item"
                        :style="{ animationDelay: (i * 60) + 'ms' }"
                    >
                        <LearningPathCardMini :path="path" />
                    </div>
                </div>
            </section>

            <section v-if="showCatalog" class="nb-paths__section">
                <h2 v-if="hasEnrolled" class="nb-paths__section-title">Not started yet</h2>

                <div class="nb-paths__grid">
                    <div
                        v-for="(path, i) in paths"
                        :key="path.id"
                        class="nb-paths__grid-item"
                        :style="{ animationDelay: (i * 70) + 'ms' }"
                    >
                        <LearningPathCardMini :path="path" />
                    </div>
                </div>
            </section>

            <section v-if="finishedPaths.length" class="nb-paths__section">
                <h2 class="nb-paths__section-title">Finished</h2>
                <div class="nb-paths__section-list">
                    <div
                        v-for="(path, i) in finishedPaths"
                        :key="path.id"
                        class="nb-paths__section-item"
                        :style="{ animationDelay: (i * 60) + 'ms' }"
                    >
                        <LearningPathCardMini :path="path" :show-finished-badge="true" />
                    </div>
                </div>
            </section>
        </main>
    </div>
</template>
