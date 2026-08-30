<script setup>
import '@/assets/scss/components/learning-path/index.scss'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import { useTheme } from '@/composables/useTheme'
import TopBar from '@/Components/TopBar.vue'

defineProps({
    paths: Array,
})

const page = usePage()
const isAuthenticated = computed(() => !!page.props.auth.user)

const { theme } = useTheme()

const EXERCISE_TYPE_META = {
    multiple_choice: { icon: 'list-check', label: 'Multiple choice' },
    true_false: { icon: 'check-double', label: 'True or false' },
    fill_in_the_blank: { icon: 'pen-to-square', label: 'Fill in the blank' },
    image_matching: { icon: 'image', label: 'Image matching' },
}

function typeMeta(type) {
    return EXERCISE_TYPE_META[type]
}

function start(pathId) {
    router.post(route('learning-paths.start', pathId))
}
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

            <div v-if="paths && paths.length" class="nb-paths__grid">
                <article
                    v-for="(path, i) in paths"
                    :key="path.id"
                    class="nb-paths__card"
                    :style="{ animationDelay: (i * 70) + 'ms' }"
                >
                    <span class="nb-paths__card-tag">{{ path.language }}</span>
                    <h2 class="nb-paths__card-name">{{ path.name }}</h2>

                    <ul v-if="path.exercise_types?.length" class="nb-paths__card-types" aria-label="Exercise types in this path">
                        <template v-for="type in path.exercise_types" :key="type">
                            <li v-if="typeMeta(type)" class="nb-paths__card-type" :title="typeMeta(type).label">
                                <font-awesome-icon :icon="typeMeta(type).icon" />
                            </li>
                        </template>
                    </ul>

                    <button
                        v-if="isAuthenticated"
                        type="button"
                        class="nb-paths__card-btn"
                        @click="start(path.id)"
                    >
                        Start
                    </button>
                    <Link v-else href="/register" class="nb-paths__card-btn">
                        Sign up to start
                    </Link>
                </article>
            </div>

            <p v-else class="nb-paths__empty">No learning paths available yet.</p>
        </main>
    </div>
</template>
