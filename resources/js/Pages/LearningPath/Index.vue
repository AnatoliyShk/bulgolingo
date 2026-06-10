<script setup>
import { Link } from '@inertiajs/vue3'
import { useTheme } from '@/composables/useTheme'

const props = defineProps({
    paths: Array,
})

const { theme, toggleTheme } = useTheme()
</script>

<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 flex flex-col items-center p-6 pt-16">
        <button
            @click="toggleTheme"
            class="fixed top-4 right-4 z-50 rounded-md border border-gray-200 bg-white px-2 py-1 text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400"
            :title="theme === 'dark' ? 'Switch to light' : 'Switch to dark'"
        >{{ theme === 'dark' ? '☀️' : '🌙' }}</button>

        <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100 text-center mb-2">All Learning Paths</h1>
        <p class="text-sm text-gray-400 dark:text-gray-500 mb-8">Choose a path to start learning</p>

        <div v-if="paths && paths.length > 0" class="paths-grid">
            <div v-for="path in paths" :key="path.id" class="path-card">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ path.name }}</h2>
                        <span class="inline-block mt-1 text-xs px-2 py-0.5 rounded-full bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400">
                            {{ path.language }}
                        </span>
                    </div>
                </div>
                <Link
                    :href="route('learning-paths.show', path.id)"
                    class="mt-4 block w-full text-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500 transition-colors"
                >
                    Start
                </Link>
            </div>
        </div>

        <div v-else class="text-center text-sm text-gray-400 dark:text-gray-500">
            No learning paths available yet.
        </div>

        <Link :href="route('dashboard')" class="mt-8 text-sm text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300">
            ← Back to dashboard
        </Link>
    </div>
</template>

<style scoped>
.paths-grid {
    width: 100%;
    max-width: 40rem;
    display: grid;
    grid-template-columns: 1fr;
    gap: 1rem;
}

@media (min-width: 640px) {
    .paths-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

.path-card {
    background: #ffffff;
    border-radius: 0.75rem;
    border: 1px solid #f3f4f6;
    box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
    padding: 1.25rem;
    transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
}

.path-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px 0 rgb(0 0 0 / 0.08);
    border-color: #a5b4fc;
}
</style>

<style>
html.dark .path-card {
    background: #1f2937;
    border-color: #374151;
}
</style>
