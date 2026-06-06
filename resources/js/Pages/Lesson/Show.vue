<script setup>
import { ref, computed, watch } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import CreateExerciseForm from '@/Components/Forms/CreateExerciseForm.vue';
import { useTheme } from '@/composables/useTheme';

const { theme, toggleTheme } = useTheme();

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
    <div class="min-h-screen bg-white dark:bg-gray-900 p-6">
        <button
            @click="toggleTheme"
            class="fixed top-4 right-4 z-50 rounded-md border border-gray-200 bg-white px-2 py-1 text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400"
            :title="theme === 'dark' ? 'Switch to light' : 'Switch to dark'"
        >{{ theme === 'dark' ? '☀️' : '🌙' }}</button>

        <h1 class="text-2xl font-bold mb-4 text-gray-900 dark:text-gray-100">{{ lesson.name }}</h1>

        <div class="mb-6">
            <h2 class="text-lg font-semibold mb-2 text-gray-800 dark:text-gray-200">Exercises</h2>
            <div v-if="exercises.length === 0" class="text-gray-500 dark:text-gray-400">No exercises yet.</div>
            <div v-for="exercise in exercises" :key="exercise.id" class="p-3 border border-gray-200 dark:border-gray-700 rounded mb-2 bg-white dark:bg-gray-800">
                <Link :href="route('exercise.show', exercise.id)" class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ exercise.name }}</Link>
            </div>
        </div>
    </div>
</template>

<style scoped>
</style>
