<script setup>
import { ref, computed, reactive, watch } from 'vue'

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
        template:    blank === -1 ? [...parts, '__'] : parts,
        blank:       blank === -1 ? parts.length : blank,
        answer:      options[clause.correct_option ?? 0] ?? '',
        choices:     options,
        translation: clause.explanation ?? '',
    }
}

const question        = computed(() => buildQuestion(props.clause))
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
    if (isCorrect.value) emit('complete')
}

function retry() {
    state.selected = null
    state.checked  = false
}
</script>

<template>
    <div>
        <p class="prompt">{{ question.translation }}</p>

        <div class="sentence">
            <template v-for="(word, i) in question.template" :key="i">
                <span
                    v-if="i === question.blank"
                    :class="['blank', { 'blank--filled': state.selected, 'blank--wrong': state.checked && !isCorrect }]"
                    @click="removeSelected"
                >
                    <span v-if="state.selected" class="blank__word">{{ state.selected }}</span>
                    <span v-else class="blank__hint">tap a word</span>
                </span>
                <span v-else class="sentence__word">{{ word }}</span>
            </template>
        </div>

        <div class="choices">
            <button
                v-for="word in shuffledChoices"
                :key="word"
                class="choice"
                :class="{ 'choice--used': state.selected === word || state.checked }"
                :disabled="state.selected === word || state.checked"
                @click="selectChoice(word)"
            >
                {{ word }}
            </button>
        </div>

        <div v-if="state.checked && !isCorrect" class="feedback feedback--wrong">
            <p class="feedback__title">✗ Incorrect — try again</p>
            <p class="feedback__body">Correct answer: <strong>{{ question.answer }}</strong></p>
        </div>

        <button
            class="btn-action"
            :disabled="!state.selected && !state.checked"
            @click="state.checked ? retry() : check()"
        >
            {{ state.checked ? (isCorrect ? 'Next Exercise' : 'Try Again') : 'Check' }}
        </button>
    </div>
</template>

<style scoped>
.prompt {
    font-family: 'PT Serif', serif;
    font-size: 1.5rem;
    font-weight: 700;
    line-height: 1.35;
    color: var(--ink);
    margin: 0 0 2rem;
}

.sentence {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: .4rem .5rem;
    padding-bottom: 1.25rem;
    border-bottom: 2px solid var(--border);
    margin-bottom: 1.75rem;
    min-height: 3rem;
}

.sentence__word { font-size: 1.15rem; color: var(--ink); }

.blank {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 5.5rem;
    height: 2.25rem;
    border-bottom: 3px solid var(--muted);
    padding: 0 .6rem;
    cursor: pointer;
    transition: border-color .2s ease;
}
.blank--filled { border-bottom-color: var(--gold); }
.blank--wrong  { border-bottom-color: var(--rose); }

.blank__word {
    font-size: 1rem;
    font-weight: 700;
    color: var(--gold);
    transition: color .2s ease;
}
.blank--wrong .blank__word { color: var(--rose); }

.blank__hint { font-size: .75rem; color: var(--muted); letter-spacing: .04em; }

.choices {
    display: flex;
    flex-wrap: wrap;
    gap: .65rem;
    margin-bottom: 2rem;
}

.choice {
    padding: .6rem 1.25rem;
    border-radius: .6rem;
    border: 1.5px solid var(--border);
    background: var(--surface);
    color: var(--ink);
    font-family: 'PT Sans', sans-serif;
    font-size: .95rem;
    font-weight: 600;
    cursor: pointer;
    transition: border-color .15s ease, transform .1s ease, color .15s ease;
}
.choice:hover:not(:disabled) { border-color: var(--rose); color: var(--rose); transform: translateY(-2px); }
.choice--used, .choice:disabled { opacity: .35; cursor: default; transform: none; }

.feedback {
    border-radius: .75rem;
    padding: .85rem 1.1rem;
    margin-bottom: 1.25rem;
    border: 1px solid transparent;
}
.feedback--wrong { background: var(--rose-bg); border-color: var(--rose); color: var(--rose); }
.feedback__title { font-weight: 700; font-size: .9rem; margin: 0 0 .25rem; }
.feedback__body  { font-size: .85rem; margin: 0; opacity: .85; }

.btn-action {
    width: 100%;
    padding: .9rem 1.5rem;
    border-radius: .75rem;
    border: none;
    background: linear-gradient(135deg, var(--rose), var(--gold));
    color: #fff6ea;
    font-family: 'PT Sans', sans-serif;
    font-size: 1rem;
    font-weight: 700;
    letter-spacing: .03em;
    cursor: pointer;
    transition: opacity .2s ease, transform .15s ease;
}
.btn-action:hover:not(:disabled) { opacity: .9; transform: translateY(-2px); }
.btn-action:disabled {
    background: var(--border);
    color: var(--muted);
    cursor: default;
    transform: none;
    opacity: 1;
}

.btn-action:focus-visible, .choice:focus-visible {
    outline: 2px solid var(--rose);
    outline-offset: 2px;
}
</style>
