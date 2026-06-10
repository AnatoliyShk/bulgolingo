<!-- resources/js/Pages/Profile/Show.vue -->
<script setup>
import { usePage, Link } from '@inertiajs/vue3'
import { useTheme } from '@/composables/useTheme'

const props = defineProps({
    user: Object,
    learningPaths: Array,
})

const page = usePage()
const { theme, toggleTheme } = useTheme()
</script>

<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 flex items-center justify-center p-6">
        <button
            @click="toggleTheme"
            class="fixed top-4 right-4 z-50 rounded-md border border-gray-200 bg-white px-2 py-1 text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400"
            :title="theme === 'dark' ? 'Switch to light' : 'Switch to dark'"
        >{{ theme === 'dark' ? '☀️' : '🌙' }}</button>

        <div class="user-card">

            <div class="flex items-center mb-6">
                <Link href="/" class="text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300 transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100 flex-1 text-center">Dashboard</h1>
                <div class="w-5"></div>
            </div>

            <div class="space-y-4">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center">
                        <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 dark:text-gray-500">Full name</p>
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ user.name }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center">
                        <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 dark:text-gray-500">Email</p>
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ user.email }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center">
                        <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 dark:text-gray-500">Member since</p>
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ user.created_at }}</p>
                    </div>
                </div>
            </div>

            <div class="mt-6 border-t border-gray-100 dark:border-gray-700 pt-5">
                <p class="text-xs uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-3">Learning Paths</p>

                <div v-if="learningPaths && learningPaths.length > 0" class="space-y-2">
                    <Link
                        v-for="path in learningPaths"
                        :key="path.id"
                        :href="route('learning-paths.show', path.id)"
                        class="flex items-center justify-between rounded-lg px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                    >
                        <span class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ path.name }}</span>
                        <span class="text-xs text-gray-400 dark:text-gray-500 ml-3">{{ path.language }}</span>
                    </Link>
                </div>

                <div v-else class="text-center py-2">
                    <Link
                        :href="route('learning-paths.index')"
                        class="inline-block rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500 transition-colors"
                    >
                        Choose path
                    </Link>
                </div>
            </div>

            <div class="text-center mt-4">
                <Link :href="route('stats.show')" class="text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300">View your stats</Link>
            </div>

        </div>
    </div>
</template>

<style scoped>
.user-card {
    background: #ffffff;
    border-radius: 1rem;
    box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
    border: 1px solid #f3f4f6;
    width: 100%;
    max-width: 28rem;
    padding: 2rem;
}

</style>

<style>
html.dark .user-card {
    background: #1f2937;
    border-color: #374151;
}
</style>
