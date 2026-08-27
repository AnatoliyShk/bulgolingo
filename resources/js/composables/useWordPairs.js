import { computed } from 'vue'

// Mirrors ExerciseType::MIN_WORD_PAIRS — 5 pairs is 10 words, 5 per language.
export const MIN_PAIRS = 5

// The empty grid a new word-pair exercise opens with.
export function emptyPairs() {
    return Array.from({ length: MIN_PAIRS }, () => ['', ''])
}

// Normalises stored pairs and tops them up so an exercise saved before the
// minimum existed still opens with enough rows to fill in.
export function padPairs(pairs) {
    const next = (pairs ?? []).map(pair => [pair?.[0] ?? '', pair?.[1] ?? ''])
    while (next.length < MIN_PAIRS) next.push(['', ''])
    return next
}

// Fisher-Yates over the pair indices, which is what a column order is: a
// permutation saying which pair each row of that column shows.
function shuffledIndices(count) {
    const indices = Array.from({ length: count }, (_, i) => i)

    for (let i = indices.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1))
        ;[indices[i], indices[j]] = [indices[j], indices[i]]
    }

    return indices
}

/**
 * Word-pair editing shared by the create and update forms: row add/remove
 * against the minimum, the Shuffle action, and the preview of what the student
 * will see. The column order lives on clause.order so the player lays the board
 * out the way the admin left it; until Shuffle is pressed there is no order and
 * the player keeps shuffling per visit.
 */
export function useWordPairs(form) {
    const pairCount = computed(() => form.clause.pairs?.length ?? 0)

    const canRemovePair = computed(() => pairCount.value > MIN_PAIRS)

    const tooFewPairs = computed(
        () => form.decision_type === 'multiple_choice' && pairCount.value < MIN_PAIRS
    )

    const hasOrder = computed(() => Array.isArray(form.clause.order?.left))

    // The clause rules report per-cell keys too (clause.pairs.3.0), so collect
    // everything under clause.pairs rather than only the top-level message.
    const pairErrors = computed(() => [
        ...new Set(
            Object.entries(form.errors)
                .filter(([key]) => key === 'clause.pairs' || key.startsWith('clause.pairs.'))
                .map(([, message]) => message)
        ),
    ])

    // The two columns as the student will read them, used for the preview.
    const orderedColumns = computed(() => {
        const pairs = form.clause.pairs ?? []
        const column = (side, cell) =>
            (form.clause.order?.[side] ?? pairs.map((_, i) => i))
                .map(index => pairs[index]?.[cell] ?? '')

        return { left: column('left', 0), right: column('right', 1) }
    })

    // Keeps a stored order in step with a row that has just been added or
    // removed, so the preview never points at a pair that moved or vanished.
    function reindexOrder(mutate) {
        if (!hasOrder.value) return

        form.clause.order = {
            left: mutate(form.clause.order.left),
            right: mutate(form.clause.order.right),
        }
    }

    function addPair() {
        form.clause.pairs.push(['', ''])
        reindexOrder(indices => [...indices, form.clause.pairs.length - 1])
    }

    function removePair(index) {
        if (!canRemovePair.value) return

        form.clause.pairs.splice(index, 1)
        reindexOrder(indices =>
            indices.filter(i => i !== index).map(i => (i > index ? i - 1 : i))
        )
    }

    function shuffleColumns() {
        form.clause.order = {
            left: shuffledIndices(pairCount.value),
            right: shuffledIndices(pairCount.value),
        }
    }

    return {
        MIN_PAIRS,
        pairCount,
        canRemovePair,
        tooFewPairs,
        hasOrder,
        pairErrors,
        orderedColumns,
        addPair,
        removePair,
        shuffleColumns,
    }
}
