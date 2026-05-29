<script setup>
const props = defineProps({
    lesson: Object,
});
</script>

<template>
    Lesson {{ props.lesson.id }}
</template>

<style scoped>

</style>
