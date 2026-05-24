<script setup>
defineProps({
    appName: {
        type: String,
        default: 'Laravel',
    },
})
</script>

<template>
    <div class="page">
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
                    <a href="/register" class="nav__link">Register</a>
                    <a href="/login" class="nav__link">Login</a>
                    <a href="#" class="nav__cta">Get started</a>
                </div>
            </nav>
        </header>

        <main>
            <!-- Hero -->
            <section class="hero">
                <div class="hero__badge">
                    <span class="hero__badge-dot" />
                    Now with Vue + Inertia
                </div>
                <h1 class="hero__title">
                    Build something<br />
                    <em>remarkable.</em>
                </h1>
                <p class="hero__subtitle">
                    A full-stack foundation built on Laravel, Vue 3, and Inertia —
                    ready for whatever you're imagining next.
                </p>
                <div class="hero__actions">
                    <a href="#" class="btn btn--primary">Start building</a>
                    <a href="#" class="btn btn--ghost">Read the docs →</a>
                </div>
            </section>

            <!-- Features -->
            <section class="features" id="features">
                <div class="features__grid">
                    <article class="card" v-for="feature in features" :key="feature.title">
                        <div class="card__icon">{{ feature.icon }}</div>
                        <h3 class="card__title">{{ feature.title }}</h3>
                        <p class="card__body">{{ feature.desc }}</p>
                    </article>
                </div>
            </section>
        </main>

        <footer class="footer">
            <p>Built with Laravel &amp; Vue · {{ new Date().getFullYear() }}</p>
        </footer>
    </div>
</template>

<script>
import '../../css/home.css'
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
