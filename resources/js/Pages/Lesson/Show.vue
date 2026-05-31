<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    lesson: Object,
    exercises: Array,
});

const exercises = computed(() => props.lesson.exercises)

</script>

<template>
    <div v-for="exercise in exercises">
        {{ exercise.name }}
    </div>
</template>

<style scoped>

</style>
