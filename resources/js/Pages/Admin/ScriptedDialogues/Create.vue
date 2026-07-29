<script setup>
import '@/assets/scss/components/admin/scripted-dialogues.scss';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import Breadcrumb from '@/Components/Breadcrumb.vue';

defineProps({
    bots: Array,
    users: Array,
});

const form = useForm({
    bot_id: '',
    user_id: '',
});

function submit() {
    form.post(route('admin.scripted-dialogues.store'));
}
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb :items="[
                { label: 'Admin', href: route('admin.index') },
                { label: 'Scripted Dialogues', href: route('admin.scripted-dialogues.index') },
                { label: 'Create' },
            ]" />
        </template>

        <div class="admin-page__body">
            <div class="admin-page__container--narrow">
                <section class="admin-card">
                    <h3 class="admin-card__title">Dialogue Details</h3>

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
                            <button type="submit" :disabled="form.processing" class="admin-btn--primary">
                                {{ form.processing ? 'Creating…' : 'Create Dialogue' }}
                            </button>
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
