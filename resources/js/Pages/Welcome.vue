<script setup>
import '../../css/home.css'
import '../../css/home-dark.css'
import '../../css/home-light.css'

import { usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { useTheme } from '@/composables/useTheme'

const page = usePage()
const isAuthenticated = computed(() => !!page.props.auth.user)
const appName = computed(() => page.props.appName)
const isAdmin = computed(() => page.props.auth.isAdmin)
const continueLessonId = computed(() => page.props.continueLessonId ?? null)
const continueHref = computed(() =>
    continueLessonId.value ? `/lesson/${continueLessonId.value}` : '/learning-paths'
)

const { theme, toggleTheme } = useTheme()

const letters = [
    {
        char: 'Ъ',
        name: 'er golyam',
        sound: 'A vowel English never writes down — close to the "a" in "sofa".',
        example: 'България',
        meaning: 'Bulgaria',
    },
    {
        char: 'Ж',
        name: 'zhe',
        sound: 'Like the "s" in "treasure", or "j" in the French "jardin".',
        example: 'жълт',
        meaning: 'yellow',
    },
    {
        char: 'Ч',
        name: 'che',
        sound: 'Like "ch" in "chair" — always crisp, never soft.',
        example: 'чай',
        meaning: 'tea',
    },
    {
        char: 'Ш',
        name: 'sha',
        sound: 'Like "sh" in "shop".',
        example: 'шапка',
        meaning: 'hat',
    },
    {
        char: 'Щ',
        name: 'shta',
        sound: '"Ш" with a tail — and a "t" fused onto the end.',
        example: 'щастие',
        meaning: 'happiness',
    },
    {
        char: 'Ю',
        name: 'yu',
        sound: 'Like "you".',
        example: 'кюфте',
        meaning: 'meatball',
    },
]

const flipped = ref(letters.map(() => false))

const steps = [
    {
        bg: 'Избери своя път',
        en: 'Pick a path',
        body: 'Every learning path is built around something you\'d actually say — ordering food, asking directions, talking to family.',
    },
    {
        bg: 'Тренирай всеки ден',
        en: 'Practice daily',
        body: 'Short, mixed exercises — multiple choice, true or false, and more — train your ear as much as your memory.',
    },
    {
        bg: 'Следи напредъка си',
        en: 'Track your progress',
        body: 'Your dashboard keeps the score: lessons finished, streaks kept, and how far the path still goes.',
    },
]
</script>

<template>
    <div class="page" :class="theme">
        <header class="header">
            <nav class="nav">
                <a href="/" class="nav__logo">
                    <span class="nav__mark" aria-hidden="true">Ъ</span>
                    {{ appName }}
                </a>
                <div class="nav__links">
                    <a href="#letters" class="nav__link">Letters</a>
                    <a href="#path" class="nav__link">Your path</a>
                    <a href="/profile" class="nav__link">Profile</a>
                    <template v-if="isAuthenticated">
                        <a href="/dashboard" class="nav__link">Dashboard</a>
                        <a href="/learning-paths" class="nav__link">Learning Path</a>
                        <a v-if="isAdmin" href="/admin" class="nav__link">Admin Panel</a>
                    </template>
                    <template v-else>
                        <a href="/login" class="nav__link">Login</a>
                        <a href="/register" class="nav__link nav__link--cta">Register</a>
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
                <span class="page__mark" aria-hidden="true">Ъ</span>

                <p class="hero__eyebrow">Учи български <span aria-hidden="true">·</span> Learn Bulgarian</p>

                <h1 class="hero__title">
                    <span class="hero__title-line">
                        <span class="hero__title-label" aria-hidden="true">from</span>
                        <span class="hero__letter" lang="bg">А</span>
                    </span>

                    <svg class="hero__thread" viewBox="0 0 240 48" aria-hidden="true" focusable="false">
                        <path class="hero__thread-strand hero__thread-strand--a" d="M4,24 C 44,4 84,44 120,24 S 196,4 236,24" />
                        <path class="hero__thread-strand hero__thread-strand--b" d="M4,24 C 44,4 84,44 120,24 S 196,4 236,24" />
                    </svg>
                    <span class="hero__thread-vertical" aria-hidden="true"></span>

                    <span class="hero__title-line">
                        <span class="hero__title-label" aria-hidden="true">to</span>
                        <span class="hero__letter hero__letter--accent" lang="bg">Я</span>
                    </span>
                </h1>

                <p class="hero__caption">in Bulgarian: «От А до Я» — start to finish, soup to nuts.</p>

                <p class="hero__subtitle">
                    <span class="hero__first-word">
                        <strong lang="bg">Здравей</strong>
                        <span class="hero__phon">zdra-VEY</span>
                    </span>
                    — that's hello, and your first word. Bulgarian has its own alphabet, its own sounds, and its own logic. Bulgolingo teaches all three.
                </p>

                <div class="hero__actions">
                    <template v-if="isAuthenticated">
                        <a :href="continueHref" class="btn btn--primary">Continue learning</a>
                        <a href="/dashboard" class="btn btn--ghost">Dashboard</a>
                    </template>
                    <template v-else>
                        <a href="/register" class="btn btn--primary">Start learning free</a>
                        <a href="/login" class="btn btn--ghost">Log in</a>
                    </template>
                </div>
            </section>

            <div class="thread-divider" aria-hidden="true"></div>

            <!-- Path -->
            <section id="path" class="path">
                <div class="section__head">
                    <p class="section__eyebrow">Твоят път · Your path</p>
                    <h2 class="section__title">How {{ appName }} works</h2>
                </div>

                <ol class="path__steps">
                    <li v-for="(step, i) in steps" :key="step.bg" class="path__step">
                        <span class="path__index">{{ i + 1 }}</span>
                        <div class="path__content">
                            <h3 class="path__title">
                                <span lang="bg">{{ step.bg }}</span>
                                <span class="path__title-en">{{ step.en }}</span>
                            </h3>
                            <p class="path__body">{{ step.body }}</p>
                        </div>
                    </li>
                </ol>
            </section>
        </main>

        <footer class="footer">
            <div class="thread-divider" aria-hidden="true"></div>
            <p class="footer__text">
                {{ appName }} — <strong lang="bg">учи български, дума по дума</strong><br>
                learn Bulgarian, one word at a time.
            </p>
        </footer>
    </div>
</template>
