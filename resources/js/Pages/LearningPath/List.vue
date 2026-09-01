<script setup>
import '@/assets/scss/components/learning-path/list.scss'
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { useTheme } from '@/composables/useTheme'
import TopBar from '@/Components/TopBar.vue'
import LearningPathCard from '@/Components/LearningPathCard.vue'

const props = defineProps({
    title: { type: String, required: true },
    emptyMessage: { type: String, default: 'No learning paths here yet.' },
    unfinishedPaths: { type: Array, default: () => [] },
    finishedPaths: { type: Array, default: () => [] },
})

const { theme } = useTheme()

const hasUnfinished = computed(() => props.unfinishedPaths.length > 0)
const hasFinished = computed(() => props.finishedPaths.length > 0)
const isEmpty = computed(() => !hasUnfinished.value && !hasFinished.value)
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

            <template v-if="!isEmpty">
                <section v-if="hasUnfinished" class="nb-path-list__section">
                    <h2 class="nb-path-list__section-title">In Progress</h2>
                    <div class="nb-path-list__paths">
                        <div
                            v-for="(path, i) in unfinishedPaths"
                            :key="path.id"
                            :style="{ animationDelay: (i * 60) + 'ms' }"
                            class="nb-path-list__path-wrapper"
                        >
                            <LearningPathCard :path="path" />
                        </div>
                    </div>
                </section>

                <section v-if="hasFinished" class="nb-path-list__section">
                    <h2 class="nb-path-list__section-title">Finished</h2>
                    <div class="nb-path-list__paths">
                        <div
                            v-for="(path, i) in finishedPaths"
                            :key="path.id"
                            :style="{ animationDelay: (i * 60) + 'ms' }"
                            class="nb-path-list__path-wrapper"
                        >
                            <LearningPathCard :path="path" :show-finished-badge="true" />
                        </div>
                    </div>
                </section>
            </template>

            <div v-else class="nb-path-list__empty">
                <p class="nb-path-list__empty-title">{{ emptyMessage }}</p>
                <Link :href="route('learning-paths.index')" class="nb-path-list__empty-btn">Browse learning paths <font-awesome-icon icon="arrow-right-long" /></Link>
            </div>
        </main>
    </div>
</template>
