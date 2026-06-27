<script setup>
import '@/assets/scss/components/admin/bots.scss';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const form = useForm({
    name: '',
    description: '',
});

function submit() {
    form.post(route('admin.bots.store'));
}
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb :items="[
                { label: 'Admin', href: route('admin.index') },
                { label: 'Bots', href: route('admin.bots.index') },
                { label: 'Create' },
            ]" />
        </template>

        <div class="admin-page__body">
            <div class="admin-page__container--narrow">
                <section class="admin-card">
                    <h3 class="admin-card__title">Bot Details</h3>

                    <form @submit.prevent="submit" class="admin-form__body">
                        <div class="admin-form__field">
                            <label for="name" class="admin-form__label">Name</label>
                            <input
                                id="name"
                                v-model="form.name"
                                type="text"
                                class="admin-form__input"
                                placeholder="Bot name"
                                autofocus
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
                                placeholder="What this bot does"
                            />
                            <p v-if="form.errors.description" class="admin-form__error">{{ form.errors.description }}</p>
                        </div>

                        <div class="admin-form__actions">
                            <Link :href="route('admin.bots.index')" class="admin-btn--cancel">Cancel</Link>
                            <button type="submit" :disabled="form.processing" class="admin-btn--primary">
                                {{ form.processing ? 'Creating…' : 'Create Bot' }}
                            </button>
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
