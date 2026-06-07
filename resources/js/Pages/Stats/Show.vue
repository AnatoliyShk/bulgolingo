<script setup>
import { computed, onMounted, ref } from 'vue'
import { useTheme } from '@/composables/useTheme'

const { theme, toggleTheme } = useTheme()

// TODO: replace with real stats from the backend
// type labels mirror App\Enums\ExerciseType::getDescription()
const lessonTypeStats = [
    { type: 'Multiple Choice', count: 14, color: '#6366f1' },
    { type: 'True/False', count: 8, color: '#22c55e' },
    { type: 'Fill in the Blank', count: 21, color: '#f59e0b' },
    { type: 'Image Matching', count: 7, color: '#ec4899' },
]

const completedLessonsCount = computed(() =>
    lessonTypeStats.reduce((sum, stat) => sum + stat.count, 0)
)

const donutSegments = computed(() => {
    const total = completedLessonsCount.value || 1
    let cumulative = 0

    return lessonTypeStats.map((stat) => {
        const percent = (stat.count / total) * 100
        const segment = {
            ...stat,
            percent: Math.round(percent),
            dashArray: `${percent} ${100 - percent}`,
            dashOffset: -cumulative,
        }
        cumulative += percent
        return segment
    })
})

const ready = ref(false)
const animatedCount = ref(0)

onMounted(() => {
    requestAnimationFrame(() => {
        ready.value = true
    })

    const target = completedLessonsCount.value
    const duration = 800
    const start = performance.now()

    function tick(now) {
        const progress = Math.min((now - start) / duration, 1)
        animatedCount.value = Math.round(target * (1 - Math.pow(1 - progress, 3)))

        if (progress < 1) {
            requestAnimationFrame(tick)
        }
    }

    requestAnimationFrame(tick)
})
</script>

<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 flex flex-col items-center p-6 pt-16">
        <button
            @click="toggleTheme"
            class="fixed top-4 right-4 z-50 rounded-md border border-gray-200 bg-white px-2 py-1 text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400"
            :title="theme === 'dark' ? 'Switch to light' : 'Switch to dark'"
        >{{ theme === 'dark' ? '☀️' : '🌙' }}</button>

        <h1 class="enter-item text-xl font-semibold text-gray-900 dark:text-gray-100 text-center mb-8" :class="{ 'is-visible': ready }">Your Stats</h1>

        <div class="stats-grid">
            <div class="stat-card enter-item flex flex-col items-center justify-center" :class="{ 'is-visible': ready }" style="transition-delay: 0.1s">
                <p class="text-xs uppercase tracking-wide text-gray-400 dark:text-gray-500">Completed lessons</p>
                <p class="text-4xl font-bold text-indigo-600 dark:text-indigo-400 mt-2">{{ animatedCount }}</p>
            </div>

            <div class="stat-card enter-item" :class="{ 'is-visible': ready }" style="transition-delay: 0.2s">
                <p class="text-xs uppercase tracking-wide text-gray-400 dark:text-gray-500 text-center mb-4">By lesson type</p>

                <div class="flex flex-col sm:flex-row items-center gap-6">
                    <svg viewBox="0 0 36 36" class="donut">
                        <circle class="donut-ring" cx="18" cy="18" r="15.9155" fill="transparent" stroke-width="3.5" />
                        <circle
                            v-for="(segment, index) in donutSegments"
                            :key="segment.type"
                            class="donut-segment"
                            cx="18" cy="18" r="15.9155"
                            fill="transparent"
                            stroke-width="3.5"
                            stroke-linecap="round"
                            :stroke="segment.color"
                            :stroke-dasharray="ready ? segment.dashArray : '0 100'"
                            :stroke-dashoffset="segment.dashOffset"
                            :style="{ transitionDelay: `${0.3 + index * 0.12}s` }"
                        />
                        <text x="18" y="17" text-anchor="middle" class="donut-count">{{ animatedCount }}</text>
                        <text x="18" y="22" text-anchor="middle" class="donut-label">lessons</text>
                    </svg>

                    <ul class="legend">
                        <li
                            v-for="(segment, index) in donutSegments"
                            :key="segment.type"
                            class="legend-item enter-item"
                            :class="{ 'is-visible': ready }"
                            :style="{ transitionDelay: `${0.35 + index * 0.08}s` }"
                        >
                            <span class="legend-dot" :style="{ backgroundColor: segment.color }"></span>
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ segment.type }}</span>
                            <span class="text-sm font-medium text-gray-400 dark:text-gray-500 ml-auto">{{ segment.percent }}%</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.enter-item {
    opacity: 0;
    transform: translateY(1rem);
    transition: opacity 0.5s ease, transform 0.5s ease;
}

.enter-item.is-visible {
    opacity: 1;
    transform: translateY(0);
}

.stats-grid {
    width: 100%;
    max-width: 40rem;
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.25rem;
}

@media (min-width: 640px) {
    .stats-grid {
        grid-template-columns: 1fr 2fr;
    }
}

.stat-card {
    background: #ffffff;
    border-radius: 0.75rem;
    border: 1px solid #f3f4f6;
    box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
    padding: 1.5rem;
}

.donut {
    width: 9rem;
    height: 9rem;
    flex-shrink: 0;
    transform: rotate(-90deg);
}

.donut-ring {
    stroke: #e5e7eb;
}

.donut-segment {
    transition: stroke-dasharray 0.6s ease;
}

.donut-count,
.donut-label {
    transform: rotate(90deg);
    transform-box: fill-box;
    transform-origin: center;
    fill: #374151;
}

.donut-count {
    font-size: 0.3rem;
    font-weight: 700;
}

.donut-label {
    font-size: 0.18rem;
    fill: #9ca3af;
}

.legend {
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.legend-dot {
    width: 0.625rem;
    height: 0.625rem;
    border-radius: 50%;
    flex-shrink: 0;
}
</style>

<style>
html.dark .stat-card {
    background: #1f2937;
    border-color: #374151;
}

html.dark .donut-ring {
    stroke: #374151;
}

html.dark .donut-count {
    fill: #f3f4f6;
}

html.dark .donut-label {
    fill: #6b7280;
}
</style>
