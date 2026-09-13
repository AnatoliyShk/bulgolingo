<script setup>
import '@/assets/scss/components/learning-path-level-filter.scss'
import { computed, ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { useTheme } from '@/composables/useTheme'

const { theme } = useTheme()
const page = usePage()

const levels = computed(() => page.props.levels ?? [])
const active = computed(() => page.props.filters?.level ?? null)
const loading = ref(false)

// Switching level keeps the search that is already narrowing the page, so the
// two filters combine instead of one silently dropping the other. Picking the
// level already active does nothing; "All" is how a level is taken off.
function choose(level) {
    if (level === active.value) {
        return
    }

    const query = page.props.search?.enabled ? page.props.search?.query : ''
    const params = {}

    if (query) {
        params.q = query
    }

    if (level) {
        params.level = level
    }

    router.get(route('learning-paths.index'), params, {
        preserveState: true,
        preserveScroll: true,
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
            type="button"
            class="nb-level-filter__option"
            :class="{ 'nb-level-filter__option--active': !active }"
            :aria-pressed="!active"
            :disabled="loading"
            @click="choose(null)"
        >
            All
        </button>
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
