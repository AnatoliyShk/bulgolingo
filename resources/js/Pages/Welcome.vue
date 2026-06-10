<script setup>
    import { usePage } from '@inertiajs/vue3'
    import { computed } from 'vue'
    import { useTheme } from '@/composables/useTheme'

    const page = usePage()
    const user = computed(() => page.props.auth.user)
    const isAuthenticated = computed(() => !!page.props.auth.user)
    const appName = computed(() => page.props.appName)
    const isAdmin = computed(() => page.props.auth.isAdmin)

    const { theme, toggleTheme } = useTheme()
</script>
<template>
    <div class="page" :class="theme">
        <!-- Background decoration -->
        <div class="bg-orb bg-orb--1" />
        <div class="bg-orb bg-orb--2" />
        <div class="bg-grid" />

        <header class="header">
            <nav class="nav">
                <span class="nav__logo">{{ appName }}</span>
                <div class="nav__links">
                    <a href="#features" class="nav__link">Features</a>
                    <a href="#about" class="nav__link">About</a>
                    <a href="/profile" class="nav__link">Profile</a>
                    <template v-if="isAuthenticated">
                        <a href="/dashboard" class="nav__link">Dashboard</a>
                        <a href="/learning-paths" class="nav__link">Learning Path</a>
                        <a v-if="isAdmin" href="/admin" class="nav__link">Admin Panel</a>
                    </template>
                    <template v-else>
                        <a href="/register" class="nav__link">Register</a>
                        <a href="/login" class="nav__link">Login</a>
                    </template>
                    <button class="theme-toggle" @click="toggleTheme" :title="theme === 'dark' ? 'Switch to light' : 'Switch to dark'">
                        {{ theme === 'dark' ? '☀️' : '🌙' }}
                    </button>
                </div>
            </nav>
        </header>

        <main>
            <!-- Hero -->
            <section class="hero">
                <h1 class="hero__title">
                    Become<br />
                    <em>Balkan</em>
                </h1>
            </section>

        </main>
    </div>
</template>

<script>
import '../../css/home.css'
import '../../css/home-dark.css'
import '../../css/home-light.css'
export default {
    data() {
        return {
            features: [
                {
                    icon: '⚡',
                    title: 'Inertia routing',
                    desc: 'Client-side navigation without building a separate API. Laravel routes power everything.',
                },
                {
                    icon: '🎨',
                    title: 'Vue 3 components',
                    desc: 'Composition API, reactive props from the server, and the full Vue ecosystem at your fingertips.',
                },
                {
                    icon: '🔥',
                    title: 'Vite HMR',
                    desc: 'Instant hot module replacement so you see changes the moment you save a file.',
                },
                {
                    icon: '🐳',
                    title: 'Laravel Sail',
                    desc: 'One command spins up PHP, MySQL, and Redis inside Docker — no local installs needed.',
                },
            ],
        }
    },
}
</script>
