<script setup>
import '@/assets/scss/components/learning-path-search.scss'
import { computed, ref, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { useTheme } from '@/composables/useTheme'

const { theme } = useTheme()
const page = usePage()

const activeQuery = computed(() => page.props.search?.query ?? '')
const unavailable = computed(() => !!page.props.search?.unavailable)
const error = computed(() => page.props.errors?.q ?? '')

const text = ref(activeQuery.value)
const searching = ref(false)

// The field follows the query the server answered for, so back and forward
// through past searches show the text that produced the list on screen.
watch(activeQuery, (query) => { text.value = query })

const matchCount = computed(() =>
    (page.props.paths?.length ?? 0)
    + (page.props.unfinishedPaths?.length ?? 0)
    + (page.props.finishedPaths?.length ?? 0),
)

const status = computed(() => {
    if (!activeQuery.value || unavailable.value) {
        return ''
    }

    if (matchCount.value === 0) {
        return `No learning paths match “${activeQuery.value}”.`
    }

    return `${matchCount.value} ${matchCount.value === 1 ? 'path matches' : 'paths match'} “${activeQuery.value}”.`
})

// Each search is its own history entry so the back button steps through them,
// and state is preserved so the field keeps its text while the list reloads.
// An empty field is a request for the whole catalog, not a search for nothing.
function visit(query) {
    router.get(route('learning-paths.index'), query ? { q: query } : {}, {
        preserveState: true,
        preserveScroll: true,
        onStart: () => { searching.value = true },
        onFinish: () => { searching.value = false },
    })
}

function submit() {
    visit(text.value.trim())
}

function clear() {
    text.value = ''
    visit('')
}
</script>

<template>
    <div class="nb-path-search" :class="theme">
        <form class="nb-path-search__form" role="search" @submit.prevent="submit">
            <input
                v-model="text"
                type="search"
                name="q"
                class="nb-path-search__input"
                :class="{ 'nb-path-search__input--invalid': error }"
                placeholder="Search by topic, e.g. food or greetings"
                aria-label="Search learning paths by topic"
                :aria-invalid="!!error"
                :aria-describedby="error ? 'nb-path-search-error' : undefined"
                maxlength="255"
                autocomplete="off"
            />
            <button
                type="submit"
                class="nb-path-search__submit"
                :disabled="searching"
                :aria-busy="searching"
            >
                <font-awesome-icon icon="magnifying-glass" aria-hidden="true" />
                <span>{{ searching ? 'Searching' : 'Search' }}</span>
            </button>
            <button
                v-if="activeQuery"
                type="button"
                class="nb-path-search__clear"
                :disabled="searching"
                @click="clear"
            >
                Clear
            </button>
        </form>

        <p v-if="error" id="nb-path-search-error" class="nb-path-search__error" role="alert">{{ error }}</p>
        <p v-else-if="unavailable" class="nb-path-search__error" role="alert">
            Search is unavailable right now. Showing every learning path instead.
        </p>
        <p v-else-if="status" class="nb-path-search__status" role="status">{{ status }}</p>
    </div>
</template>
