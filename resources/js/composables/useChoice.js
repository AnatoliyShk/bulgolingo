import { computed, ref, watch } from 'vue'

// Pick-one-then-check state for players whose first pick is the answer: the
// choice locks on click and is cleared by retry or by a new clause.
export function useChoice(clause, answer) {
    const chosen  = ref(null)
    const checked = ref(false)

    const isCorrect = computed(() => checked.value && chosen.value === answer.value)

    function select(value) {
        if (checked.value) return
        chosen.value  = value
        checked.value = true
    }

    function retry() {
        chosen.value  = null
        checked.value = false
    }

    watch(clause, retry, { deep: true })

    return { chosen, checked, isCorrect, select, retry }
}
