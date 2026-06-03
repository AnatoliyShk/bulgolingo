<script setup>
import { ref, computed, watch } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import CreateExerciseForm from '@/Components/Forms/CreateExerciseForm.vue';

const props = defineProps({
    lesson: Object,
    exerciseTypes: Array,
});

const exercises = computed(() => props.lesson.exercises);

const showForm = ref(false);

const form = useForm({
    lesson_id: props.lesson.id,
    name: '',
    decision_type: '',
    clause: {
        sentence: '',
        options: ['', '', '', ''],
        correct_option: 0,
        explanation: '',
    },
});

// Reset clause fields whenever the type changes
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
            showForm.value = false;
        },
    });
}
</script>

<template>
    <div class="p-6">
        <h1 class="text-2xl font-bold mb-4">{{ lesson.name }}</h1>

        <div class="mb-6">
            <h2 class="text-lg font-semibold mb-2">Exercises</h2>
            <div v-if="exercises.length === 0" class="text-gray-500">No exercises yet.</div>
            <div v-for="exercise in exercises" :key="exercise.id" class="p-3 border rounded mb-2">
                <Link :href="route('exercise.show', exercise.id)" class="text-sm font-medium text-gray-800">{{ exercise.name }}</Link>
            </div>
        </div>

        <button
            @click="showForm = !showForm"
            class="mb-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
        >
            {{ showForm ? 'Cancel' : 'Add Exercise' }}
        </button>

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

            <CreateExerciseForm
                v-if="showForm"
                :lesson-id="lesson.id"
                :exercise-types="exerciseTypes"
                @success="showForm = false"
                @cancel="showForm = false"
            />
    </div>
</template>

<style scoped>
</style>
