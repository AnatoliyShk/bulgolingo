<script setup>
import { ref, computed, reactive, watch } from 'vue'
import NextExerciseButton from '@/Components/NextExerciseButton.vue'

const props = defineProps({
    clause: { type: Object, required: true },
})

const emit = defineEmits(['complete'])

const shuffle = (arr) => [...arr].sort(() => Math.random() - 0.5)

function buildQuestion(clause) {
    const parts   = (clause.sentence ?? '').split(/\s+/)
    const blank   = parts.findIndex(w => w === '__')
    const options = clause.options ?? []
    return {
        template: blank === -1 ? [...parts, '__'] : parts,
        blank:    blank === -1 ? parts.length : blank,
        answer:   options[clause.correct_option ?? 0] ?? '',
        choices:  options,
    }
}

const question        = computed(() => buildQuestion(props.clause))
const explanation     = computed(() => props.clause.explanation ?? '')
const shuffledChoices = ref(shuffle(question.value.choices))

watch(() => props.clause, () => {
    shuffledChoices.value = shuffle(question.value.choices)
    state.selected = null
    state.checked  = false
}, { deep: true })

const state = reactive({
    selected: null,
    checked:  false,
})

const isCorrect = computed(() => state.checked && state.selected === question.value.answer)

function selectChoice(word) {
    if (state.checked) return
    state.selected = word
}

function removeSelected() {
    if (state.checked) return
    state.selected = null
}

function check() {
    if (!state.selected || state.checked) return
    state.checked = true
}

function retry() {
    state.selected = null
    state.checked  = false
}
</script>

<template>
    <div>
        <p class="nb-ex-prompt">Fill in the blank</p>

        <div class="nb-ex-sentence">
            <template v-for="(word, i) in question.template" :key="i">
                <span
                    v-if="i === question.blank"
                    :class="['nb-ex-blank', { 'nb-ex-blank--filled': state.selected, 'nb-ex-blank--wrong': state.checked && !isCorrect }]"
                    @click="removeSelected"
                >
                    <span v-if="state.selected" class="nb-ex-blank__word">{{ state.selected }}</span>
                    <span v-else class="nb-ex-blank__hint">tap a word</span>
                </span>
                <span v-else class="nb-ex-sentence__word">{{ word }}</span>
            </template>
        </div>

        <div class="nb-ex-choices">
            <button
                v-for="word in shuffledChoices"
                :key="word"
                class="nb-ex-opt nb-ex-opt--inline"
                :class="{ 'nb-ex-opt--used': state.selected === word || state.checked }"
                :disabled="state.selected === word || state.checked"
                @click="selectChoice(word)"
            >
                {{ word }}
            </button>
        </div>

        <div v-if="state.checked && !isCorrect" class="nb-ex-feedback nb-ex-feedback--wrong">
            <p class="nb-ex-feedback__title">✗ Incorrect — try again</p>
            <p class="nb-ex-feedback__body">Correct answer: <strong>{{ question.answer }}</strong></p>
            <p v-if="explanation" class="nb-ex-feedback__body">{{ explanation }}</p>
        </div>

        <div v-if="state.checked && isCorrect" class="nb-ex-feedback nb-ex-feedback--correct">
            <p class="nb-ex-feedback__title">✓ Correct!</p>
            <p v-if="explanation" class="nb-ex-feedback__body">{{ explanation }}</p>
        </div>

        <NextExerciseButton v-if="state.checked && isCorrect" @advance="emit('complete')" />

        <button
            v-else
            class="nb-ex-action"
            :disabled="!state.selected && !state.checked"
            @click="state.checked ? retry() : check()"
        >
            {{ state.checked ? 'Try Again' : 'Check' }}
        </button>
    </div>
</template>
