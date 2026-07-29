<script setup>
import '@/assets/scss/components/admin/bots.scss';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
    bot: Object,
});

const form = useForm({
    name: props.bot.name,
    description: props.bot.description ?? '',
});

function submit() {
    form.patch(route('admin.bots.update', props.bot.id));
}
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb :items="[
                { label: 'Admin', href: route('admin.index') },
                { label: 'Bots', href: route('admin.bots.index') },
                { label: bot.name },
            ]" />
        </template>

        <div class="admin-page__body">
            <div class="admin-page__container--narrow">
                <section class="admin-card">
                    <h3 class="admin-card__title">Bot Details</h3>

                    <form @submit.prevent="submit" class="admin-form__body">
                        <div class="admin-form__field">
                            <label class="admin-form__label">Name</label>
                            <input v-model="form.name" type="text" class="admin-form__input" />
                            <p v-if="form.errors.name" class="admin-form__error">{{ form.errors.name }}</p>
                        </div>

                        <div class="admin-form__field">
                            <label class="admin-form__label">Description</label>
                            <textarea v-model="form.description" rows="4" class="admin-form__textarea" />
                            <p v-if="form.errors.description" class="admin-form__error">{{ form.errors.description }}</p>
                        </div>

                        <div class="admin-form__actions">
                            <Link :href="route('admin.bots.index')" class="admin-btn--cancel">Cancel</Link>
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
