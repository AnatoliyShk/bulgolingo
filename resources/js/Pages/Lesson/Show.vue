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
    </div>
</template>

<style scoped>
</style>
