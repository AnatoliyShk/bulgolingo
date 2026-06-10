<script setup>
import { watch } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    lesson: Object,
    exerciseTypes: Array,
});

function defaultClause(type) {
    if (type === 'true_false') {
        return {
            sentence: '',
            correct_option: true,
            explanation: '',
        };
    }

    if (type === 'multiple_choice') {
        return {
            pairs: [['', ''], ['', ''], ['', ''], ['', '']],
            explanation: '',
        };
    }

    return {
        sentence: '',
        options: ['', '', '', ''],
        correct_option: 0,
        explanation: '',
    };
}

const form = useForm({
    lesson_id: props.lesson.id,
    name: '',
    decision_type: '',
    clause: defaultClause(''),
});

watch(() => form.decision_type, (newType) => {
    form.clause = defaultClause(newType);
});

function addPair() {
    form.clause.pairs.push(['', '']);
}

function removePair(index) {
    form.clause.pairs.splice(index, 1);
}

function submit() {
    form.post(route('admin.exercises.store', props.lesson.id));
}
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link
                    :href="route('admin.lessons.edit', lesson.id)"
                    class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                >← {{ lesson.name }}</Link>
                <span class="text-gray-300 dark:text-gray-600">/</span>
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Create Exercise
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
                <section class="rounded-lg border border-gray-200 bg-white p-6 shadow dark:border-gray-700 dark:bg-gray-800">
                    <form @submit.prevent="submit" class="space-y-5">

                        <!-- Name -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
                            <input
                                v-model="form.name"
                                type="text"
                                class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-400 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                                placeholder="Exercise name"
                                autofocus
                            />
                            <p v-if="form.errors.name" class="mt-1 text-xs text-red-500">{{ form.errors.name }}</p>
                        </div>

                        <!-- Type -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                            <select
                                v-model="form.decision_type"
                                class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-400 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                            >
                                <option value="" disabled>Select a type</option>
                                <option v-for="type in exerciseTypes" :key="type.value" :value="type.value">
                                    {{ type.label }}
                                </option>
                            </select>
                            <p v-if="form.errors.decision_type" class="mt-1 text-xs text-red-500">{{ form.errors.decision_type }}</p>
                        </div>

                        <!-- Multiple Choice fields -->
                        <template v-if="form.decision_type === 'multiple_choice'">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Word pairs</label>
                                <div
                                    v-for="(pair, index) in form.clause.pairs"
                                    :key="index"
                                    class="flex items-center gap-2 mb-2"
                                >
                                    <input
                                        v-model="pair[0]"
                                        type="text"
                                        class="flex-1 rounded-lg border border-gray-300 px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                                        placeholder="Word"
                                    />
                                    <input
                                        v-model="pair[1]"
                                        type="text"
                                        class="flex-1 rounded-lg border border-gray-300 px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                                        placeholder="Translation"
                                    />
                                    <button
                                        type="button"
                                        @click="removePair(index)"
                                        class="text-xs text-red-500 hover:underline dark:text-red-400"
                                    >Remove</button>
                                </div>
                                <button
                                    type="button"
                                    @click="addPair"
                                    class="text-xs font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400"
                                >+ Add pair</button>
                                <p v-if="form.errors['clause.pairs']" class="mt-1 text-xs text-red-500">{{ form.errors['clause.pairs'] }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Explanation</label>
                                <textarea
                                    v-model="form.clause.explanation"
                                    rows="3"
                                    class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-400 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 resize-none"
                                    placeholder="Explain the correct answer"
                                />
                                <p v-if="form.errors['clause.explanation']" class="mt-1 text-xs text-red-500">{{ form.errors['clause.explanation'] }}</p>
                            </div>
                        </template>

                        <!-- True/False fields -->
                        <template v-if="form.decision_type === 'true_false'">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sentence</label>
                                <input
                                    v-model="form.clause.sentence"
                                    type="text"
                                    class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-400 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                                    placeholder="e.g. The sky is green."
                                />
                                <p v-if="form.errors['clause.sentence']" class="mt-1 text-xs text-red-500">{{ form.errors['clause.sentence'] }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Correct answer</label>
                                <select
                                    v-model="form.clause.correct_option"
                                    class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-400 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                                >
                                    <option :value="true">True</option>
                                    <option :value="false">False</option>
                                </select>
                                <p v-if="form.errors['clause.correct_option']" class="mt-1 text-xs text-red-500">{{ form.errors['clause.correct_option'] }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Explanation</label>
                                <textarea
                                    v-model="form.clause.explanation"
                                    rows="3"
                                    class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-400 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 resize-none"
                                    placeholder="Explain the correct answer"
                                />
                                <p v-if="form.errors['clause.explanation']" class="mt-1 text-xs text-red-500">{{ form.errors['clause.explanation'] }}</p>
                            </div>
                        </template>

                        <!-- Fill in the Blank fields -->
                        <template v-if="form.decision_type === 'fill_in_the_blank'">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sentence</label>
                                <input
                                    v-model="form.clause.sentence"
                                    type="text"
                                    class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-400 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                                    placeholder="e.g. The ___ is on the table"
                                />
                                <p v-if="form.errors['clause.sentence']" class="mt-1 text-xs text-red-500">{{ form.errors['clause.sentence'] }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Options</label>
                                <div
                                    v-for="(_, index) in form.clause.options"
                                    :key="index"
                                    class="flex items-center gap-2 mb-2"
                                >
                                    <span class="text-xs text-gray-400 w-4">{{ index + 1 }}.</span>
                                    <input
                                        v-model="form.clause.options[index]"
                                        type="text"
                                        class="flex-1 rounded-lg border border-gray-300 px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                                        :placeholder="`Option ${index + 1}`"
                                    />
                                </div>
                                <p v-if="form.errors['clause.options']" class="mt-1 text-xs text-red-500">{{ form.errors['clause.options'] }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Correct option (0-based index)</label>
                                <input
                                    v-model.number="form.clause.correct_option"
                                    type="number"
                                    min="0"
                                    :max="form.clause.options.length - 1"
                                    class="w-24 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                                />
                                <p v-if="form.errors['clause.correct_option']" class="mt-1 text-xs text-red-500">{{ form.errors['clause.correct_option'] }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Explanation</label>
                                <textarea
                                    v-model="form.clause.explanation"
                                    rows="3"
                                    class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-400 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 resize-none"
                                    placeholder="Explain the correct answer"
                                />
                                <p v-if="form.errors['clause.explanation']" class="mt-1 text-xs text-red-500">{{ form.errors['clause.explanation'] }}</p>
                            </div>
                        </template>

                        <!-- Actions -->
                        <div class="flex items-center justify-end gap-3 pt-1">
                            <Link
                                :href="route('admin.lessons.edit', lesson.id)"
                                class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400"
                            >Cancel</Link>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50 transition"
                            >
                                {{ form.processing ? 'Creating…' : 'Create Exercise' }}
                            </button>
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
