<script setup>
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    lessonId: {
        type: Number,
        required: true,
    },
    exerciseTypes: {
        type: Array,
        required: true,
    },
});

const emit = defineEmits(['success', 'cancel']);

const form = useForm({
    lesson_id: props.lessonId,
    name: '',
    decision_type: '',
    clause: {
        sentence: '',
        options: ['', '', '', ''],
        correct_option: 0,
        explanation: '',
    },
});

watch(() => form.decision_type, () => {
    form.clause = {
        sentence: '',
        options: ['', '', '', ''],
        correct_option: 0,
        explanation: '',
    };
});

function submit() {
    form.post(route('exercise.store'), {
        onSuccess: () => {
            form.reset('name', 'decision_type', 'clause');
            emit('success');
        },
    });
}
</script>

<template>
    <form @submit.prevent="submit" class="border rounded p-4 space-y-4">
        <h2 class="text-lg font-semibold">New Exercise</h2>

        <div>
            <label class="block text-sm font-medium mb-1">Name</label>
            <input
                v-model="form.name"
                type="text"
                class="w-full border rounded px-3 py-2"
                placeholder="Exercise name"
            />
            <p v-if="form.errors.name" class="text-red-500 text-sm mt-1">{{ form.errors.name }}</p>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Type</label>
            <select v-model="form.decision_type" class="w-full border rounded px-3 py-2">
                <option value="" disabled>Select a type</option>
                <option v-for="type in exerciseTypes" :key="type.value" :value="type.value">
                    {{ type.label }}
                </option>
            </select>
            <p v-if="form.errors.decision_type" class="text-red-500 text-sm mt-1">{{ form.errors.decision_type }}</p>
        </div>

        <!-- Fill in the Blank fields -->
        <template v-if="form.decision_type === 'fill_in_the_blank'">
            <div>
                <label class="block text-sm font-medium mb-1">Sentence</label>
                <input
                    v-model="form.clause.sentence"
                    type="text"
                    class="w-full border rounded px-3 py-2"
                    placeholder="e.g. The ___ is on the table"
                />
                <p v-if="form.errors['clause.sentence']" class="text-red-500 text-sm mt-1">{{ form.errors['clause.sentence'] }}</p>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Options</label>
                <div
                    v-for="(_, index) in form.clause.options"
                    :key="index"
                    class="flex items-center gap-2 mb-2"
                >
                    <span class="text-sm w-4">{{ index + 1 }}.</span>
                    <input
                        v-model="form.clause.options[index]"
                        type="text"
                        class="flex-1 border rounded px-3 py-2"
                        :placeholder="`Option ${index + 1}`"
                    />
                </div>
                <p v-if="form.errors['clause.options']" class="text-red-500 text-sm mt-1">{{ form.errors['clause.options'] }}</p>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Correct Option (0-based index)</label>
                <input
                    v-model.number="form.clause.correct_option"
                    type="number"
                    min="0"
                    :max="form.clause.options.length - 1"
                    class="w-full border rounded px-3 py-2"
                />
                <p v-if="form.errors['clause.correct_option']" class="text-red-500 text-sm mt-1">{{ form.errors['clause.correct_option'] }}</p>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Explanation</label>
                <textarea
                    v-model="form.clause.explanation"
                    class="w-full border rounded px-3 py-2"
                    rows="3"
                    placeholder="Explain the correct answer"
                />
                <p v-if="form.errors['clause.explanation']" class="text-red-500 text-sm mt-1">{{ form.errors['clause.explanation'] }}</p>
            </div>
        </template>

        <div class="flex gap-2">
            <button
                type="submit"
                :disabled="form.processing"
                class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 disabled:opacity-50"
            >
                Save Exercise
            </button>
            <button
                type="button"
                @click="emit('cancel')"
                class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300"
            >
                Cancel
            </button>
        </div>
    </form>
</template>
