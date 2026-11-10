<script setup>
import '@/assets/scss/components/admin/lessons.scss';
import { computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';

// Without a lesson the form creates one; with a lesson it edits that lesson.
const props = defineProps({
    lesson: { type: Object, default: null },
});

const isEditing = props.lesson !== null;

const form = useForm({
    name: props.lesson?.name ?? '',
    description: props.lesson?.description ?? '',
});

const submitLabel = computed(() => {
    if (form.processing) return isEditing ? 'Saving…' : 'Creating…';
    return isEditing ? 'Save' : 'Create Lesson';
});

function submit() {
    if (isEditing) {
        form.patch(route('admin.lessons.update', props.lesson.id));
    } else {
        form.post(route('admin.lessons.store'));
    }
}
</script>

<template>
    <form @submit.prevent="submit" class="admin-form__body">
        <div class="admin-form__field">
            <label for="name" class="admin-form__label">Name</label>
            <input
                id="name"
                v-model="form.name"
                type="text"
                class="admin-form__input"
                placeholder="Lesson name"
                :autofocus="!isEditing"
            />
            <p v-if="form.errors.name" class="admin-form__error">{{ form.errors.name }}</p>
        </div>

        <div class="admin-form__field">
            <label for="description" class="admin-form__label">Description</label>
            <textarea
                id="description"
                v-model="form.description"
                rows="4"
                class="admin-form__textarea"
                placeholder="Lesson description"
            />
            <p v-if="form.errors.description" class="admin-form__error">{{ form.errors.description }}</p>
        </div>

        <div class="admin-form__actions">
            <Link :href="route('admin.lessons.index')" class="admin-btn--cancel">Cancel</Link>
            <button type="submit" :disabled="form.processing" class="admin-btn--primary">{{ submitLabel }}</button>
        </div>
    </form>
</template>
