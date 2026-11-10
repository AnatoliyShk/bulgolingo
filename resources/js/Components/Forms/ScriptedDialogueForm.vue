<script setup>
import '@/assets/scss/components/admin/scripted-dialogues.scss';
import { computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';

// Without a dialogue the form creates one; with a dialogue it edits it.
const props = defineProps({
    dialogue: { type: Object, default: null },
    bots:     { type: Array, required: true },
    users:    { type: Array, required: true },
});

const isEditing = props.dialogue !== null;

const form = useForm({
    bot_id: props.dialogue?.bot_id ?? '',
    user_id: props.dialogue?.user_id ?? '',
});

const submitLabel = computed(() => {
    if (form.processing) return isEditing ? 'Saving…' : 'Creating…';
    return isEditing ? 'Save' : 'Create Dialogue';
});

function submit() {
    if (isEditing) {
        form.patch(route('admin.scripted-dialogues.update', props.dialogue.id));
    } else {
        form.post(route('admin.scripted-dialogues.store'));
    }
}
</script>

<template>
    <form @submit.prevent="submit" class="admin-form__body">
        <div class="admin-form__field">
            <label for="bot_id" class="admin-form__label">Bot</label>
            <select id="bot_id" v-model="form.bot_id" class="admin-form__select">
                <option value="" disabled>Select a bot</option>
                <option v-for="bot in bots" :key="bot.id" :value="bot.id">{{ bot.name }}</option>
            </select>
            <p v-if="form.errors.bot_id" class="admin-form__error">{{ form.errors.bot_id }}</p>
        </div>

        <div class="admin-form__field">
            <label for="user_id" class="admin-form__label">User</label>
            <select id="user_id" v-model="form.user_id" class="admin-form__select">
                <option value="" disabled>Select a user</option>
                <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
            </select>
            <p v-if="form.errors.user_id" class="admin-form__error">{{ form.errors.user_id }}</p>
        </div>

        <div class="admin-form__actions">
            <Link :href="route('admin.scripted-dialogues.index')" class="admin-btn--cancel">Cancel</Link>
            <button type="submit" :disabled="form.processing" class="admin-btn--primary">{{ submitLabel }}</button>
        </div>
    </form>
</template>
