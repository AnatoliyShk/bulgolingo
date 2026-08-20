<script setup>
import '@/assets/scss/components/lesson/show.scss'
import { Head, router } from '@inertiajs/vue3'
import { useTheme } from '@/composables/useTheme'

const { theme, toggleTheme } = useTheme()

const props = defineProps({ lesson: Object })

function restart() {
    router.post(route('lesson.restart', props.lesson.id))
}

function goBack() {
    history.back()
}
</script>

<template>
    <Head>
        <link
            href="https://fonts.bunny.net/css?family=unbounded:400,600,700,800,900|manrope:400,500,600,700,800&subset=cyrillic,latin&display=swap"
            rel="stylesheet"
        />
    </Head>

    <div class="nb-lesson" :class="theme">
        <button
            class="nb-lesson__toggle"
            @click="toggleTheme"
            :title="theme === 'dark' ? 'Switch to light' : 'Switch to dark'"
        >
            {{ theme === 'dark' ? '☀' : '☾' }}
        </button>

        <main class="nb-lesson__sheet">
            <div class="nb-lesson__card">
                <div class="nb-lesson__icon">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                        />
                    </svg>
                </div>

                <p class="nb-lesson__eyebrow">
                    <span class="nb-lesson__badge" lang="bg">Урок</span>
                    <span class="nb-lesson__meta">Completed</span>
                </p>

                <h1 class="nb-lesson__title">Restart lesson?</h1>

                <p class="nb-lesson__body">
                    You've already completed
                    <strong class="nb-lesson__name">{{ lesson.name }}</strong>.
                    Do you want to go through it again?
                </p>

                <div class="nb-lesson__actions">
                    <button class="nb-lesson__action nb-lesson__action--primary" @click="restart">
                        Yes, restart
                    </button>
                    <button class="nb-lesson__action" @click="goBack">
                        No, go back
                    </button>
                </div>
            </div>
        </main>
    </div>
</template>
