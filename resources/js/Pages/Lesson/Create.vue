<script setup>
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import ThemeToggle from '@/Components/ThemeToggle.vue'

const props = defineProps({
    exerciseTypes: Array,
})

const EXERCISE_TYPES = computed(() => props.exerciseTypes)

function emptyExercise() {
    return { name: '', decision_type: 'fill_in_the_blank', clause: {} }
}

function emptyClauseEntry() {
    return { key: '', value: '' }
}

const form = useForm({
    name: '',
    description: '',
    exercises: [],
})

// ── Exercise modal state ───────────────────────────────────────────
const showModal  = ref(false)
const editIndex  = ref(null)        // null = add, number = edit
const modalError = ref('')

const draft      = ref(emptyExercise())
const clauseRows = ref([emptyClauseEntry()])

function openAdd() {
    editIndex.value  = null
    draft.value      = emptyExercise()
    clauseRows.value = [emptyClauseEntry()]
    modalError.value = ''
    showModal.value  = true
}

function openEdit(index) {
    editIndex.value  = index
    const ex         = form.exercises[index]
    draft.value      = { name: ex.name, decision_type: ex.decision_type, clause: {} }
    clauseRows.value = Object.entries(ex.clause).map(([key, value]) => ({ key, value }))
    if (clauseRows.value.length === 0) clauseRows.value = [emptyClauseEntry()]
    modalError.value = ''
    showModal.value  = true
}

function addClauseRow()  { clauseRows.value.push(emptyClauseEntry()) }
function removeClauseRow(i) { clauseRows.value.splice(i, 1) }

function buildClause() {
    const obj = {}
    for (const row of clauseRows.value) {
        if (row.key.trim()) obj[row.key.trim()] = row.value
    }
    return obj
}

function saveExercise() {
    if (!draft.value.name.trim()) {
        modalError.value = 'Exercise name is required.'
        return
    }
    const exercise = { ...draft.value, clause: buildClause() }
    if (editIndex.value === null) {
        form.exercises.push(exercise)
    } else {
        form.exercises[editIndex.value] = exercise
    }
    showModal.value = false
}

function removeExercise(index) {
    form.exercises.splice(index, 1)
}

// ── Submission ─────────────────────────────────────────────────────
function submit() {
    form.post(route('lesson.store'))
}
</script>

<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 flex items-center justify-center p-6">
        <ThemeToggle class="fixed top-4 right-4 z-50" />

        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 w-full max-w-2xl p-8">
            <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100 text-center mb-6">Create Lesson</h1>

            <form @submit.prevent="submit" class="space-y-5">

                <!-- Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
                    <input
                        id="name"
                        v-model="form.name"
                        type="text"
                        class="w-full rounded-lg border border-gray-200 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-4 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-400"
                        placeholder="Lesson name"
                    />
                    <p v-if="form.errors.name" class="mt-1 text-xs text-red-500">{{ form.errors.name }}</p>
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                    <textarea
                        id="description"
                        v-model="form.description"
                        rows="3"
                        class="w-full rounded-lg border border-gray-200 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-4 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-400 resize-none"
                        placeholder="Lesson description"
                    />
                    <p v-if="form.errors.description" class="mt-1 text-xs text-red-500">{{ form.errors.description }}</p>
                </div>

                <!-- Exercises list -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Exercises</span>
                        <button
                            type="button"
                            @click="openAdd"
                            class="text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 font-medium"
                        >+ Add exercise</button>
                    </div>

                    <div v-if="form.exercises.length === 0" class="text-xs text-gray-400 dark:text-gray-500 text-center py-4 border border-dashed border-gray-200 dark:border-gray-600 rounded-lg">
                        No exercises yet
                    </div>

                    <ul v-else class="space-y-2">
                        <li
                            v-for="(ex, i) in form.exercises"
                            :key="i"
                            class="flex items-center justify-between rounded-lg border border-gray-100 dark:border-gray-700 px-4 py-3 bg-gray-50 dark:bg-gray-700"
                        >
                            <div>
                                <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ ex.name }}</p>
                                <p class="text-xs text-gray-400 dark:text-gray-500">{{ EXERCISE_TYPES.find(t => t.value === ex.decision_type)?.label }}</p>
                            </div>
                            <div class="flex gap-3">
                                <button type="button" @click="openEdit(i)" class="text-xs text-indigo-500 dark:text-indigo-400 hover:text-indigo-700">Edit</button>
                                <button type="button" @click="removeExercise(i)" class="text-xs text-red-400 hover:text-red-600">Remove</button>
                            </div>
                        </li>
                    </ul>

                    <p v-if="form.errors.exercises" class="mt-1 text-xs text-red-500">{{ form.errors.exercises }}</p>
                </div>

                <!-- Submit -->
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white text-sm font-medium rounded-lg px-4 py-2 transition"
                >
                    {{ form.processing ? 'Creating…' : 'Create Lesson' }}
                </button>
            </form>
        </div>
    </div>

    <!-- Exercise modal -->
    <Teleport to="body">
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg w-full max-w-md p-6 space-y-4">
                <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">
                    {{ editIndex === null ? 'Add Exercise' : 'Edit Exercise' }}
                </h2>

                <!-- Exercise name -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
                    <input
                        v-model="draft.name"
                        type="text"
                        class="w-full rounded-lg border border-gray-200 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-4 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-400"
                        placeholder="Exercise name"
                    />
                </div>

                <!-- Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                    <select
                        v-model="draft.decision_type"
                        class="w-full rounded-lg border border-gray-200 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-4 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-400"
                    >
                        <option v-for="t in EXERCISE_TYPES" :key="t.value" :value="t.value">{{ t.label }}</option>
                    </select>
                </div>

                <!-- Clause (key-value pairs) -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Clause</label>
                        <button type="button" @click="addClauseRow" class="text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-800">+ Add field</button>
                    </div>
                    <div class="space-y-2">
                        <div v-for="(row, i) in clauseRows" :key="i" class="flex gap-2 items-center">
                            <input
                                v-model="row.key"
                                type="text"
                                placeholder="key"
                                class="w-2/5 rounded-lg border border-gray-200 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-1.5 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-400"
                            />
                            <input
                                v-model="row.value"
                                type="text"
                                placeholder="value"
                                class="flex-1 rounded-lg border border-gray-200 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-1.5 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-400"
                            />
                            <button
                                type="button"
                                @click="removeClauseRow(i)"
                                class="text-gray-300 dark:text-gray-600 hover:text-red-400 text-lg leading-none"
                                :disabled="clauseRows.length === 1"
                            >&times;</button>
                        </div>
                    </div>
                </div>

                <p v-if="modalError" class="text-xs text-red-500">{{ modalError }}</p>

                <!-- Modal actions -->
                <div class="flex justify-end gap-3 pt-1">
                    <button
                        type="button"
                        @click="showModal = false"
                        class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700"
                    >Cancel</button>
                    <button
                        type="button"
                        @click="saveExercise"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg px-4 py-2 transition"
                    >Save</button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
