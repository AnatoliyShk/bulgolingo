<script setup>
import { router } from '@inertiajs/vue3';
import { useTheme } from '@/composables/useTheme';

const { theme, toggleTheme } = useTheme();

const props = defineProps({ lesson: Object });

function restart() {
    router.post(route('lesson.restart', props.lesson.id));
}

function goBack() {
    history.back();
}
</script>

<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900 px-4">
        <button
            @click="toggleTheme"
            class="fixed top-4 right-4 z-50 rounded-md border border-gray-200 bg-white px-2 py-1 text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400"
            :title="theme === 'dark' ? 'Switch to light' : 'Switch to dark'"
        >{{ theme === 'dark' ? '☀️' : '🌙' }}</button>

        <div class="w-full max-w-sm rounded-2xl border border-gray-200 bg-white p-8 shadow-lg dark:border-gray-700 dark:bg-gray-800">
            <div class="mb-6 text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-indigo-100 dark:bg-indigo-900">
                    <svg class="h-7 w-7 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </div>
                <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100">Restart lesson?</h2>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    You've already completed <span class="font-medium text-gray-700 dark:text-gray-300">{{ lesson.name }}</span>. Do you want to go through it again?
                </p>
            </div>

            <div class="flex flex-col gap-3">
                <button
                    @click="restart"
                    class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 transition-colors"
                >
                    Yes, restart
                </button>
                <button
                    @click="goBack"
                    class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                >
                    No, go back
                </button>
            </div>
        </div>
    </div>
</template>
