<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import { useTheme } from '@/composables/useTheme'

const props = defineProps({
    lessons: Array,
})

const { theme, toggleTheme } = useTheme()

function deleteLesson(id) {
    useForm({}).delete(route('lesson.destroy', id))
}

// TODO: replace with real lesson.completed from pivot
function isCompleted(index) {
    return index < 2
}
</script>

<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 flex flex-col items-center p-6 pt-16">
        <button
            @click="toggleTheme"
            class="fixed top-4 right-4 z-50 rounded-md border border-gray-200 bg-white px-2 py-1 text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400"
            :title="theme === 'dark' ? 'Switch to light' : 'Switch to dark'"
        >{{ theme === 'dark' ? '☀️' : '🌙' }}</button>

        <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100 text-center mb-8">Learning Path</h1>

        <div class="timeline">
            <div
                v-for="(lesson, index) in lessons"
                :key="lesson.id"
                class="timeline-item"
                :class="{ 'is-completed': isCompleted(index) }"
            >
                <div class="timeline-side">
                    <div class="timeline-dot"></div>
                </div>

                <div class="lesson-card">
                    <Link :href="route('lesson.show', lesson.id)" class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ lesson.description }}</Link>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ lesson.name }}</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ lesson.created_at }}</p>
                </div>
            </div>
        </div>

        <Link :href="route('lesson.create')" class="mt-4 text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300">Create Lesson</Link>
    </div>
</template>

<style scoped>
.timeline {
    width: 100%;
    max-width: 32rem;
}

.timeline-item {
    display: flex;
    gap: 1.25rem;
    position: relative;
    padding-bottom: 1.5rem;
}

/* Connector line from dot down to next item */
.timeline-item:not(:last-child) .timeline-side::after {
    content: '';
    position: absolute;
    left: 0.4375rem;
    top: calc(0.5rem + 1rem);
    bottom: 0;
    width: 2px;
    background: #d1d5db;
    transition: background 0.3s ease;
}

.timeline-item.is-completed:not(:last-child) .timeline-side::after {
    background: #6366f1;
}

.timeline-side {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    flex-shrink: 0;
    padding-top: 0.5rem;
}

.timeline-dot {
    width: 1rem;
    height: 1rem;
    border-radius: 50%;
    border: 2px solid #d1d5db;
    background: #ffffff;
    flex-shrink: 0;
    transition: background 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
    z-index: 1;
}

.is-completed .timeline-dot {
    background: #6366f1;
    border-color: #6366f1;
    box-shadow: 0 0 0 3px #e0e7ff;
}

.lesson-card {
    background: #ffffff;
    border-radius: 0.75rem;
    border: 1px solid #f3f4f6;
    box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
    padding: 1rem;
    width: 100%;
    transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
}

.lesson-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px 0 rgb(0 0 0 / 0.08);
    border-color: #a5b4fc;
}
</style>

<style>
html.dark .timeline-dot {
    background: #1f2937;
    border-color: #4b5563;
}

html.dark .is-completed .timeline-dot {
    background: #6366f1;
    border-color: #6366f1;
    box-shadow: 0 0 0 3px #312e81;
}

html.dark .timeline-item:not(:last-child) .timeline-side::after {
    background: #4b5563;
}

html.dark .timeline-item.is-completed:not(:last-child) .timeline-side::after {
    background: #6366f1;
}

html.dark .lesson-card {
    background: #1f2937;
    border-color: #374151;
}
</style>
