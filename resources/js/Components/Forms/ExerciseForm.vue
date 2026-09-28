<script setup>
import '@/assets/scss/components/admin/exercises.scss';
import { computed, watch } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import ImageUpload from '@/Components/Forms/ImageUpload.vue';
import { MIN_PAIRS, padPairs, useWordPairs } from '@/composables/useWordPairs';

// Without an exercise the form creates one for lessonId; with one it edits
// it. The clause shape of every type comes from the server with
// exerciseTypes, so nothing here restates what a clause holds.
const props = defineProps({
    exercise:      { type: Object, default: null },
    exerciseTypes: { type: Array,  required: true },
    submitRoute:   { type: String, required: true },
    lessonId:      { type: Number, default: null },
    cancelHref:    { type: String, default: null },
});

const emit = defineEmits(['success', 'cancel']);

const isEditing = props.exercise !== null;

// A type's clause as the form edits it: the blank clause the server sends
// with the type, with each field the exercise already holds taking the blank
// one's place. Stored pairs are padded so an exercise saved before the pair
// minimum existed still opens with enough rows, and fields the blank clause
// does not name, such as a stored column order, are left behind because
// saving an edit deals a new order anyway. The result is a plain copy, so
// editing it never writes through to the exercise prop.
function clauseFor(type, stored = {}) {
    const blank = props.exerciseTypes.find(t => t.value === type)?.clause ?? {};
    const clause = Object.fromEntries(
        Object.keys(blank).map(field => [field, stored[field] ?? blank[field]])
    );

    if ('pairs' in clause) {
        clause.pairs = padPairs(clause.pairs);
    }

    return JSON.parse(JSON.stringify(clause));
}

const form = useForm({
    ...(props.lessonId !== null ? { lesson_id: props.lessonId } : {}),
    name: props.exercise?.name ?? '',
    decision_type: props.exercise?.decision_type ?? '',
    clause: clauseFor(props.exercise?.decision_type ?? '', props.exercise?.clause ?? {}),
    image: null,
});

const existingImageUrl = computed(() => props.exercise?.images?.[0]?.url ?? null);

const submitLabel = computed(() => {
    if (form.processing) return isEditing ? 'Saving…' : 'Creating…';
    return isEditing ? 'Update Exercise' : 'Create Exercise';
});

watch(() => form.decision_type, (newType) => {
    form.clause = clauseFor(newType);
    form.image = null;
});

const {
    pairCount,
    canRemovePair,
    tooFewPairs,
    hasOrder,
    pairErrors,
    orderedColumns,
    addPair,
    removePair,
    shuffleColumns,
} = useWordPairs(form);

function submit() {
    if (tooFewPairs.value) return;
    form.submit(isEditing ? 'put' : 'post', props.submitRoute, {
        onSuccess: () => emit('success'),
    });
}
</script>

<template>
    <form @submit.prevent="submit" class="space-y-5">

        <!-- Name -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
            <input
                v-model="form.name"
                type="text"
                class="admin-form__input"
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
                class="admin-form__input"
            >
                <option v-if="!isEditing" value="" disabled>Select a type</option>
                <option v-for="type in exerciseTypes" :key="type.value" :value="type.value">
                    {{ type.label }}
                </option>
            </select>
            <p v-if="form.errors.decision_type" class="mt-1 text-xs text-red-500">{{ form.errors.decision_type }}</p>
        </div>

        <!-- Image (image matching only) -->
        <ImageUpload
            v-if="form.decision_type === 'image_matching'"
            :key="form.decision_type"
            :existing-url="existingImageUrl"
            :error="form.errors.image"
            @change="form.image = $event"
        />

        <!-- Multiple Choice -->
        <template v-if="form.decision_type === 'multiple_choice'">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Word pairs</label>
                <p class="mb-2 text-xs text-gray-400 dark:text-gray-500">
                    At least {{ MIN_PAIRS }} pairs: 10 words, 5 per language.
                </p>
                <div
                    v-for="(pair, index) in form.clause.pairs"
                    :key="index"
                    class="flex items-center gap-2 mb-2"
                >
                    <input
                        v-model="pair[0]"
                        type="text"
                        class="admin-form__input--row"
                        placeholder="Word"
                    />
                    <input
                        v-model="pair[1]"
                        type="text"
                        class="admin-form__input--row"
                        placeholder="Translation"
                    />
                    <button
                        type="button"
                        @click="removePair(index)"
                        :disabled="!canRemovePair"
                        :title="canRemovePair ? 'Remove this pair' : `At least ${MIN_PAIRS} pairs are required`"
                        class="admin-btn--remove-sm"
                    >Remove</button>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        @click="addPair"
                        class="text-xs font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400"
                    >+ Add pair</button>
                    <button
                        v-if="!isEditing"
                        type="button"
                        @click="shuffleColumns"
                        class="text-xs font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400"
                    >Shuffle</button>
                    <span class="text-xs text-gray-400 dark:text-gray-500">
                        {{ pairCount }} pairs · {{ pairCount * 2 }} words
                    </span>
                </div>
                <div v-if="!isEditing && hasOrder" data-testid="student-order" class="mt-3 rounded border border-gray-200 px-3 py-2 dark:border-gray-700">
                    <p class="mb-1 text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        Order the student sees
                    </p>
                    <div class="grid grid-cols-2 gap-4">
                        <ol class="space-y-0.5 text-xs text-gray-500 dark:text-gray-400">
                            <li v-for="(word, index) in orderedColumns.left" :key="`left-${index}`">
                                {{ word || '—' }}
                            </li>
                        </ol>
                        <ol class="space-y-0.5 text-xs text-gray-500 dark:text-gray-400">
                            <li v-for="(word, index) in orderedColumns.right" :key="`right-${index}`">
                                {{ word || '—' }}
                            </li>
                        </ol>
                    </div>
                </div>
                <p v-if="isEditing" class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                    Saving deals a new order for both columns.
                </p>
                <p v-if="tooFewPairs" class="mt-1 text-xs text-red-500">
                    Add at least {{ MIN_PAIRS }} pairs before saving.
                </p>
                <p v-for="message in pairErrors" :key="message" class="mt-1 text-xs text-red-500">{{ message }}</p>
            </div>
        </template>

        <!-- True/False -->
        <template v-if="form.decision_type === 'true_false'">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sentence</label>
                <input
                    v-model="form.clause.sentence"
                    type="text"
                    class="admin-form__input"
                    placeholder="e.g. The sky is green."
                />
                <p v-if="form.errors['clause.sentence']" class="mt-1 text-xs text-red-500">{{ form.errors['clause.sentence'] }}</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Correct answer</label>
                <select
                    v-model="form.clause.correct_option"
                    class="admin-form__input"
                >
                    <option :value="true">True</option>
                    <option :value="false">False</option>
                </select>
                <p v-if="form.errors['clause.correct_option']" class="mt-1 text-xs text-red-500">{{ form.errors['clause.correct_option'] }}</p>
            </div>
        </template>

        <!-- Image Matching -->
        <template v-if="form.decision_type === 'image_matching'">
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
                        class="admin-form__input--row"
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
                    class="admin-form__input--narrow"
                />
                <p v-if="form.errors['clause.correct_option']" class="mt-1 text-xs text-red-500">{{ form.errors['clause.correct_option'] }}</p>
            </div>
        </template>

        <!-- Fill in the Blank -->
        <template v-if="form.decision_type === 'fill_in_the_blank'">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sentence</label>
                <input
                    v-model="form.clause.sentence"
                    type="text"
                    class="admin-form__input"
                    placeholder="e.g. The __ is on the table"
                />
                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Use <code class="font-mono">__</code> to mark the blank position in the sentence.</p>
                <p v-if="form.errors['clause.sentence']" class="mt-1 text-xs text-red-500">{{ form.errors['clause.sentence'] }}</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Options</label>
                <div
                    v-for="(_, index) in form.clause.options"
                    :key="index"
                    class="flex items-center gap-2 mb-2 rounded-lg border px-2 py-1 transition-colors"
                    :class="form.clause.correct_option === index
                        ? 'border-green-500 outline outline-1 outline-green-500 bg-green-900/40'
                        : 'border-transparent'"
                >
                    <span class="text-xs text-gray-400 w-4">{{ index + 1 }}.</span>
                    <input
                        v-model="form.clause.options[index]"
                        type="text"
                        class="admin-form__input--row"
                        :placeholder="`Option ${index + 1}`"
                    />
                    <input
                        type="checkbox"
                        :checked="form.clause.correct_option === index"
                        @change="form.clause.correct_option = index"
                        class="w-4 h-4 accent-green-500"
                        :title="`Mark option ${index + 1} as correct`"
                    />
                </div>
                <p v-if="form.errors['clause.options']" class="mt-1 text-xs text-red-500">{{ form.errors['clause.options'] }}</p>
                <p v-if="form.errors['clause.correct_option']" class="mt-1 text-xs text-red-500">{{ form.errors['clause.correct_option'] }}</p>
            </div>
        </template>

        <!-- Explanation (every type) -->
        <div v-if="form.decision_type">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Explanation</label>
            <textarea
                v-model="form.clause.explanation"
                rows="3"
                class="admin-form__textarea"
                placeholder="Explain the correct answer"
            />
            <p v-if="form.errors['clause.explanation']" class="mt-1 text-xs text-red-500">{{ form.errors['clause.explanation'] }}</p>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-3 pt-1">
            <Link
                v-if="cancelHref"
                :href="cancelHref"
                class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400"
            >Cancel</Link>
            <button
                v-else
                type="button"
                @click="emit('cancel')"
                class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400"
            >Cancel</button>
            <button
                type="submit"
                :disabled="form.processing || tooFewPairs"
                class="admin-btn--primary"
            >
                {{ submitLabel }}
            </button>
        </div>
    </form>
</template>
