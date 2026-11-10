<script setup>
import '@/assets/scss/components/admin/messengers.scss';
import { computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';

// Without a messenger the form creates one; with a messenger it edits it.
const props = defineProps({
    messenger:      { type: Object, default: null },
    users:          { type: Array, required: true },
    messengerNames: { type: Array, required: true },
});

const isEditing = props.messenger !== null;

const form = useForm({
    user_id: props.messenger?.user_id ?? '',
    messenger_name: props.messenger?.messenger_name ?? '',
    messenger_user_id: props.messenger?.messenger_user_id ?? '',
});

const submitLabel = computed(() => {
    if (form.processing) return isEditing ? 'Saving…' : 'Creating…';
    return isEditing ? 'Save' : 'Create Messenger';
});

function submit() {
    if (isEditing) {
        form.patch(route('admin.messengers.update', props.messenger.id));
    } else {
        form.post(route('admin.messengers.store'));
    }
}
</script>

<template>
    <form @submit.prevent="submit" class="admin-form__body">
        <div class="admin-form__field">
            <label for="user_id" class="admin-form__label">User</label>
            <select id="user_id" v-model="form.user_id" class="admin-form__select">
                <option value="" disabled>Select a user</option>
                <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
            </select>
            <p v-if="form.errors.user_id" class="admin-form__error">{{ form.errors.user_id }}</p>
        </div>

        <div class="admin-form__field">
            <label for="messenger_name" class="admin-form__label">Messenger Name</label>
            <select id="messenger_name" v-model="form.messenger_name" class="admin-form__select" :autofocus="!isEditing">
                <option value="" disabled>Select a messenger</option>
                <option v-for="option in messengerNames" :key="option.value" :value="option.value">{{ option.label }}</option>
            </select>
            <p v-if="form.errors.messenger_name" class="admin-form__error">{{ form.errors.messenger_name }}</p>
        </div>

        <div class="admin-form__field">
            <label for="messenger_user_id" class="admin-form__label">Messenger User ID</label>
            <input
                id="messenger_user_id"
                v-model="form.messenger_user_id"
                type="text"
                class="admin-form__input"
                placeholder="Identifier from the messenger system"
            />
            <p v-if="form.errors.messenger_user_id" class="admin-form__error">{{ form.errors.messenger_user_id }}</p>
        </div>

        <div class="admin-form__actions">
            <Link :href="route('admin.messengers.index')" class="admin-btn--cancel">Cancel</Link>
            <button type="submit" :disabled="form.processing" class="admin-btn--primary">{{ submitLabel }}</button>
        </div>
    </form>
</template>
