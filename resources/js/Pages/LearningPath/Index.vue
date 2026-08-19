<script setup>
import '@/assets/scss/components/learning-path/index.scss'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { useTheme } from '@/composables/useTheme'

defineProps({
    paths: Array,
})

const page = usePage()
const appName = computed(() => page.props.appName ?? 'Bulgolingo')
const isAuthenticated = computed(() => !!page.props.auth.user)

const { theme, toggleTheme } = useTheme()
const mobileNavOpen = ref(false)

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
        <header class="nb-paths__bar">
            <nav class="nb-paths__bar-inner">
                <Link href="/" class="nb-paths__logo">
                    <span class="nb-paths__logo-mark" aria-hidden="true">BB</span>
                    <span class="nb-paths__logo-text">{{ appName }}</span>
                </Link>

                <div class="nb-paths__bar-group">
                    <div class="nb-paths__bar-links" :class="{ 'nb-paths__bar-links--open': mobileNavOpen }">
                        <template v-if="isAuthenticated">
                            <Link href="/dashboard" class="nb-paths__navlink" @click="mobileNavOpen = false">Dashboard</Link>
                        </template>
                        <template v-else>
                            <Link href="/login" class="nb-paths__navlink" @click="mobileNavOpen = false">Login</Link>
                            <Link href="/register" class="nb-paths__navlink nb-paths__navlink--cta" @click="mobileNavOpen = false">Register</Link>
                        </template>
                    </div>

                    <div class="nb-paths__bar-actions">
                        <button
                            class="nb-paths__toggle"
                            @click="toggleTheme"
                            :title="theme === 'dark' ? 'Switch to light' : 'Switch to dark'"
                        >
                            {{ theme === 'dark' ? '☀' : '☾' }}
                        </button>

                        <button
                            class="nb-paths__hamburger"
                            type="button"
                            :aria-expanded="mobileNavOpen"
                            aria-label="Toggle navigation menu"
                            @click="mobileNavOpen = !mobileNavOpen"
                        >
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path v-if="!mobileNavOpen" stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16" />
                                <path v-else stroke-linecap="round" d="M6 6l12 12M18 6L6 18" />
                            </svg>
                        </button>
                    </div>
                </div>
            </nav>
        </header>

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
