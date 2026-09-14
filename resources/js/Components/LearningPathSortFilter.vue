<script setup>
import '@/assets/scss/components/learning-path-sort-filter.scss'
import { computed, ref } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useTheme } from '@/composables/useTheme'
import { useLearningPathFilters } from '@/composables/useLearningPathFilters'

const { theme } = useTheme()
const page = usePage()
const filters = useLearningPathFilters()

const OPTIONS = [
    { value: 'exercises_desc', label: 'Most exercises' },
    { value: 'exercises_asc', label: 'Fewest exercises' },
]

const active = computed(() => page.props.filters?.sort)
const loading = ref(false)

// Switching sort keeps the search and the level already narrowing the page,
// so they combine instead of one silently dropping the other. Picking the
// sort already active does nothing. The catalog always carries a sort — most
// exercises first by default — so there is no "off" option to pick here.
function choose(sort) {
    if (sort === active.value) {
        return
    }

    filters.visit({ sort }, {
        onStart: () => { loading.value = true },
        onFinish: () => { loading.value = false },
    })
}
</script>

<template>
    <div
        class="nb-sort-filter"
        :class="theme"
        role="group"
        aria-label="Sort learning paths by exercise count"
    >
        <span class="nb-sort-filter__label" aria-hidden="true">Exercises</span>
        <button
            v-for="option in OPTIONS"
            :key="option.value"
            type="button"
            class="nb-sort-filter__option"
            :class="{ 'nb-sort-filter__option--active': active === option.value }"
            :aria-pressed="active === option.value"
            :disabled="loading"
            @click="choose(option.value)"
        >
            {{ option.label }}
        </button>
    </div>
</template>
