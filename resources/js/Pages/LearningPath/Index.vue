<script setup>
import '@/assets/scss/components/learning-path/index.scss'
import { computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import { useTheme } from '@/composables/useTheme'
import TopBar from '@/Components/TopBar.vue'
import LearningPathCardMini from '@/Components/LearningPathCardMini.vue'

const props = defineProps({
    paths: Array,
    unfinishedPaths: { type: Array, default: () => [] },
    finishedPaths: { type: Array, default: () => [] },
})

const { theme } = useTheme()

const hasEnrolled = computed(() => props.unfinishedPaths.length > 0 || props.finishedPaths.length > 0)
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
            </div>

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

            <section class="nb-paths__section">
                <h2 v-if="hasEnrolled" class="nb-paths__section-title">Not started yet</h2>

                <div v-if="paths && paths.length" class="nb-paths__grid">
                    <div
                        v-for="(path, i) in paths"
                        :key="path.id"
                        class="nb-paths__grid-item"
                        :style="{ animationDelay: (i * 70) + 'ms' }"
                    >
                        <LearningPathCardMini :path="path" />
                    </div>
                </div>

                <p v-else class="nb-paths__empty">
                    {{ hasEnrolled ? "You've started every path we have. More are on the way." : 'No learning paths available yet.' }}
                </p>
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
