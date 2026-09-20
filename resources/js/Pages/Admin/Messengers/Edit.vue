<script setup>
import '@/assets/scss/components/admin/messengers.scss';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
    messenger: Object,
    users: Array,
});

const form = useForm({
    user_id: props.messenger.user_id,
    messenger_name: props.messenger.messenger_name,
    messenger_user_id: props.messenger.messenger_user_id,
});

function submit() {
    form.patch(route('admin.messengers.update', props.messenger.id));
}
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb :items="[
                { label: 'Admin', href: route('admin.index') },
                { label: 'Messengers', href: route('admin.messengers.index') },
                { label: messenger.messenger_name },
            ]" />
        </template>

        <div class="admin-page__body">
            <div class="admin-page__container--narrow">
                <section class="admin-card">
                    <h3 class="admin-card__title">Messenger Details</h3>

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
                            <input id="messenger_name" v-model="form.messenger_name" type="text" class="admin-form__input" />
                            <p v-if="form.errors.messenger_name" class="admin-form__error">{{ form.errors.messenger_name }}</p>
                        </div>

                        <div class="admin-form__field">
                            <label for="messenger_user_id" class="admin-form__label">Messenger User ID</label>
                            <input id="messenger_user_id" v-model="form.messenger_user_id" type="text" class="admin-form__input" />
                            <p v-if="form.errors.messenger_user_id" class="admin-form__error">{{ form.errors.messenger_user_id }}</p>
                        </div>

                        <div class="admin-form__actions">
                            <Link :href="route('admin.messengers.index')" class="admin-btn--cancel">Cancel</Link>
                            <button type="submit" :disabled="form.processing" class="admin-btn--primary">
                                {{ form.processing ? 'Saving…' : 'Save' }}
                            </button>
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
