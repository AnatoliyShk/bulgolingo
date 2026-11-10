<script setup>
import '@/assets/scss/components/admin/learning-paths.scss';
import { computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';

// Without a path the form creates one; with a path it edits that path and
// carries its lesson_ids, which the edit page's lesson picker changes through
// the form handed to the default slot.
const props = defineProps({
    learningPath: { type: Object, default: null },
    types:        { type: Array, required: true },
    levels:       { type: Array, required: true },
});

const isEditing = props.learningPath !== null;

const form = useForm({
    name: props.learningPath?.name ?? '',
    language: props.learningPath?.language ?? '',
    type: props.learningPath?.type ?? props.types[0]?.value ?? 'regular',
    level: props.learningPath?.level ?? null,
    ...(isEditing ? { lesson_ids: props.learningPath.lessons.map(l => l.id) } : {}),
});

const submitLabel = computed(() => {
    if (form.processing) return isEditing ? 'Saving…' : 'Creating…';
    return isEditing ? 'Save' : 'Create Path';
});

function submit() {
    if (isEditing) {
        form.put(route('admin.learning-paths.update', props.learningPath.id));
    } else {
        form.post(route('admin.learning-paths.store'));
    }
}
</script>

<template>
    <form @submit.prevent="submit" class="admin-form__body">
        <div class="admin-form__field">
            <label for="learning-path-name" class="admin-form__label">Name</label>
            <input
                id="learning-path-name"
                v-model="form.name"
                type="text"
                class="admin-form__input"
                placeholder="e.g. Beginner Bulgarian"
                :autofocus="!isEditing"
            />
            <p v-if="form.errors.name" class="admin-form__error">{{ form.errors.name }}</p>
        </div>

        <div class="admin-form__field">
            <label for="learning-path-language" class="admin-form__label">Language</label>
            <input
                id="learning-path-language"
                v-model="form.language"
                type="text"
                class="admin-form__input"
                placeholder="e.g. Bulgarian"
            />
            <p v-if="form.errors.language" class="admin-form__error">{{ form.errors.language }}</p>
        </div>

        <div class="admin-form__field">
            <label for="learning-path-type" class="admin-form__label">Type</label>
            <select id="learning-path-type" v-model="form.type" class="admin-form__select">
                <option v-for="option in types" :key="option.value" :value="option.value">{{ option.label }}</option>
            </select>
            <p v-if="form.errors.type" class="admin-form__error">{{ form.errors.type }}</p>
        </div>

        <div class="admin-form__field">
            <label for="learning-path-level" class="admin-form__label">Level</label>
            <select id="learning-path-level" v-model="form.level" class="admin-form__select">
                <option :value="null">Not set</option>
                <option v-for="option in levels" :key="option.value" :value="option.value">{{ option.label }}</option>
            </select>
            <p v-if="form.errors.level" class="admin-form__error">{{ form.errors.level }}</p>
        </div>

        <slot :form="form" />

        <div class="admin-form__actions">
            <Link :href="route('admin.learning-paths.index')" class="admin-btn--cancel">Cancel</Link>
            <button type="submit" :disabled="form.processing" class="admin-btn--primary">{{ submitLabel }}</button>
        </div>
    </form>
</template>
