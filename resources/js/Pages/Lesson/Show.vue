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
                <h1 class="nb-lesson__title">
                    You've already completed
                    <span class="nb-lesson__name">{{ lesson.name }}</span>.
                    Do you want to go through it again?
                </h1>

                <div class="nb-lesson__actions">
                    <button class="nb-lesson__action nb-lesson__action--primary" @click="restart">
                        Yes
                    </button>
                    <button class="nb-lesson__action" @click="goBack">
                        No
                    </button>
                </div>
            </div>
        </main>
    </div>
</template>
