<script setup>
import '@/assets/scss/components/learning-path-level-prompt.scss'
import { computed, onMounted, ref, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useTheme } from '@/composables/useTheme'
import { useLearningPathFilters } from '@/composables/useLearningPathFilters'

const { theme } = useTheme()
const page = usePage()
const filters = useLearningPathFilters()
const dialog = ref()
const loading = ref(false)

const levels = computed(() => page.props.levels ?? [])

// Nothing narrows the catalog yet — no ?level in the address and no cookie
// from an earlier visit — so the prompt blocks the page until the visitor
// picks one. There being no level to offer a choice among is the one
// exception: an empty catalog gets no prompt either.
const show = computed(() => !page.props.filters?.level && levels.value.length > 0)

function sync(value) {
    if (value) {
        dialog.value?.showModal()
    } else {
        dialog.value?.close()
    }
}

onMounted(() => sync(show.value))
watch(show, sync)

function choose(level) {
    filters.visit({ level }, {
        onStart: () => { loading.value = true },
        onFinish: () => { loading.value = false },
    })
}
</script>

<template>
    <dialog
        ref="dialog"
        class="nb-level-prompt"
        :class="theme"
        aria-label="Choose your level"
        @cancel.prevent
    >
        <h2 class="nb-level-prompt__title">What's your level?</h2>
        <p class="nb-level-prompt__sub">Pick a level to start browsing — you can switch it any time from the catalog.</p>

        <div class="nb-level-prompt__options">
            <button
                v-for="level in levels"
                :key="level.value"
                type="button"
                class="nb-level-prompt__option"
                :disabled="loading"
                @click="choose(level.value)"
            >
                {{ level.label }}
            </button>
        </div>
    </dialog>
</template>
