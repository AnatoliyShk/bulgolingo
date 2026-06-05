<script setup>
import { ref, watch } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CreateExerciseForm from '@/Components/Forms/CreateExerciseForm.vue';

const props = defineProps({
    lesson: Object,
    exerciseTypes: Array,
});

// ── Lesson form ────────────────────────────────────────────────────────
const lessonForm = useForm({
    name: props.lesson.name,
    description: props.lesson.description ?? '',
});

function saveLesson() {
    lessonForm.patch(route('admin.lessons.update', props.lesson.id));
}

// ── Exercise inline editing ────────────────────────────────────────────
const showAddForm = ref(false);
const editingExercise = ref(null);

const exerciseForm = useForm({
    name: '',
    decision_type: '',
    clause: {
        sentence: '',
        options: ['', '', '', ''],
        correct_option: 0,
        explanation: '',
    },
});

function openExerciseEdit(exercise) {
    editingExercise.value = exercise.id;
    exerciseForm.name = exercise.name;
    exerciseForm.decision_type = exercise.decision_type;
    exerciseForm.clause = {
        sentence: exercise.clause?.sentence ?? '',
        options: exercise.clause?.options ?? ['', '', '', ''],
        correct_option: exercise.clause?.correct_option ?? 0,
        explanation: exercise.clause?.explanation ?? '',
    };
}

watch(() => exerciseForm.decision_type, (newType, oldType) => {
    if (newType === oldType) return;
    exerciseForm.clause = {
        sentence: '',
        options: ['', '', '', ''],
        correct_option: 0,
        explanation: '',
    };
});

function saveExercise(exerciseId) {
    exerciseForm.put(route('exercise.update', exerciseId), {
        onSuccess: () => { editingExercise.value = null; },
    });
}

function cancelExerciseEdit() {
    editingExercise.value = null;
}

function deleteExercise(exerciseId) {
    if (confirm('Delete this exercise?')) {
        router.delete(route('admin.exercises.destroy', exerciseId));
    }
}
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link
                    :href="route('admin.lessons.index')"
                    class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                >← Lessons</Link>
                <span class="text-gray-300 dark:text-gray-600">/</span>
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Edit Lesson
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl space-y-8 px-4 sm:px-6 lg:px-8">

                <!-- ── Lesson details ───────────────────────────────── -->
                <section class="rounded-lg border border-gray-200 bg-white p-6 shadow dark:border-gray-700 dark:bg-gray-800">
                    <h3 class="mb-4 text-base font-semibold text-gray-800 dark:text-gray-200">Lesson Details</h3>
                    <form @submit.prevent="saveLesson" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
                            <input
                                v-model="lessonForm.name"
                                type="text"
                                class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-400 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                            />
                            <p v-if="lessonForm.errors.name" class="mt-1 text-xs text-red-500">{{ lessonForm.errors.name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                            <textarea
                                v-model="lessonForm.description"
                                rows="3"
                                class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-400 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                            />
                            <p v-if="lessonForm.errors.description" class="mt-1 text-xs text-red-500">{{ lessonForm.errors.description }}</p>
                        </div>
                        <div class="flex justify-end">
                            <button
                                type="submit"
                                :disabled="lessonForm.processing"
                                class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                            >
                                {{ lessonForm.processing ? 'Saving…' : 'Save' }}
                            </button>
                        </div>
                    </form>
                </section>

                <!-- ── Exercises ───────────────────────────────────── -->
                <section class="rounded-lg border border-gray-200 bg-white p-6 shadow dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200">
                            Exercises
                            <span class="ml-2 text-xs font-normal text-gray-400">({{ lesson.exercises.length }})</span>
                        </h3>
                        <button
                            type="button"
                            @click="showAddForm = !showAddForm"
                            class="text-xs font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400"
                        >
                            {{ showAddForm ? '✕ Cancel' : '+ Add Exercise' }}
                        </button>
                    </div>

                    <div v-if="showAddForm" class="mb-6">
                        <CreateExerciseForm
                            :lesson-id="lesson.id"
                            :exercise-types="exerciseTypes"
                            @success="showAddForm = false"
                            @cancel="showAddForm = false"
                        />
                    </div>

                    <div v-if="lesson.exercises.length === 0 && !showAddForm" class="text-sm text-gray-500 dark:text-gray-400">
                        No exercises yet.
                    </div>

                    <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                        <li v-for="exercise in lesson.exercises" :key="exercise.id" class="py-4">

                            <!-- ── View mode ──────────────────────── -->
                            <div v-if="editingExercise !== exercise.id" class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ exercise.name }}</p>
                                    <p class="text-xs text-gray-400">
                                        {{ exerciseTypes.find(t => t.value === exercise.decision_type)?.label ?? exercise.decision_type }}
                                    </p>
                                </div>
                                <div class="flex gap-4">
                                    <button
                                        type="button"
                                        @click="openExerciseEdit(exercise)"
                                        class="text-xs text-indigo-600 hover:underline dark:text-indigo-400"
                                    >Edit</button>
                                    <button
                                        type="button"
                                        @click="deleteExercise(exercise.id)"
                                        class="text-xs text-red-500 hover:underline dark:text-red-400"
                                    >Delete</button>
                                </div>
                            </div>

                            <!-- ── Edit mode ──────────────────────── -->
                            <form v-else @submit.prevent="saveExercise(exercise.id)" class="space-y-3 rounded-lg border border-indigo-200 bg-indigo-50 p-4 dark:border-indigo-800 dark:bg-indigo-950/30">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
                                    <input
                                        v-model="exerciseForm.name"
                                        type="text"
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                                    />
                                    <p v-if="exerciseForm.errors.name" class="mt-1 text-xs text-red-500">{{ exerciseForm.errors.name }}</p>
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                                    <select
                                        v-model="exerciseForm.decision_type"
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                                    >
                                        <option v-for="type in exerciseTypes" :key="type.value" :value="type.value">
                                            {{ type.label }}
                                        </option>
                                    </select>
                                </div>

                                <!-- fill_in_the_blank fields -->
                                <template v-if="exerciseForm.decision_type === 'fill_in_the_blank'">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Sentence</label>
                                        <input
                                            v-model="exerciseForm.clause.sentence"
                                            type="text"
                                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                                            placeholder="e.g. The ___ is on the table"
                                        />
                                        <p v-if="exerciseForm.errors['clause.sentence']" class="mt-1 text-xs text-red-500">{{ exerciseForm.errors['clause.sentence'] }}</p>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Options</label>
                                        <div v-for="(_, i) in exerciseForm.clause.options" :key="i" class="flex items-center gap-2 mb-2">
                                            <span class="text-xs text-gray-400 w-4">{{ i + 1 }}.</span>
                                            <input
                                                v-model="exerciseForm.clause.options[i]"
                                                type="text"
                                                class="flex-1 rounded-lg border border-gray-300 px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                                                :placeholder="`Option ${i + 1}`"
                                            />
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Correct option (0-based index)</label>
                                        <input
                                            v-model.number="exerciseForm.clause.correct_option"
                                            type="number"
                                            min="0"
                                            :max="exerciseForm.clause.options.length - 1"
                                            class="w-24 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                                        />
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Explanation</label>
                                        <textarea
                                            v-model="exerciseForm.clause.explanation"
                                            rows="2"
                                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                                        />
                                    </div>
                                </template>

                                <div class="flex justify-end gap-3 pt-1">
                                    <button
                                        type="button"
                                        @click="cancelExerciseEdit"
                                        class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400"
                                    >Cancel</button>
                                    <button
                                        type="submit"
                                        :disabled="exerciseForm.processing"
                                        class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                                    >
                                        {{ exerciseForm.processing ? 'Saving…' : 'Save Exercise' }}
                                    </button>
                                </div>
                            </form>

                        </li>
                    </ul>
                </section>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
