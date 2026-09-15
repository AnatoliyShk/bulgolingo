<script setup>
import '@/assets/scss/components/learning-path-level-filter.scss'
import { computed, ref } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useTheme } from '@/composables/useTheme'
import { useLearningPathFilters } from '@/composables/useLearningPathFilters'

const { theme } = useTheme()
const page = usePage()
const filters = useLearningPathFilters()

const levels = computed(() => page.props.levels ?? [])
const active = computed(() => page.props.filters?.level ?? null)
const loading = ref(false)

// Switching level keeps the search and the other filters already narrowing
// the page, so they combine instead of one silently dropping the rest.
// Picking the level already active does nothing; there is no way to clear
// the filter back to every level, since the catalog always defaults to A2.
function choose(level) {
    if (level === active.value) {
        return
    }

    filters.visit({ level }, {
        onStart: () => { loading.value = true },
        onFinish: () => { loading.value = false },
    })
}
</script>

<template>
    <div
        v-if="levels.length"
        class="nb-level-filter"
        :class="theme"
        role="group"
        aria-label="Filter learning paths by level"
    >
        <span class="nb-level-filter__label" aria-hidden="true">Level</span>
        <button
            v-for="level in levels"
            :key="level.value"
            type="button"
            class="nb-level-filter__option"
            :class="{ 'nb-level-filter__option--active': active === level.value }"
            :aria-pressed="active === level.value"
            :aria-label="level.label"
            :title="level.label"
            :disabled="loading"
            @click="choose(level.value)"
        >
            {{ level.value }}
        </button>
    </div>
</template>
