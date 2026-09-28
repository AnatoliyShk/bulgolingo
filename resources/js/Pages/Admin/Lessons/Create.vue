<script setup>
import '@/assets/scss/components/admin/lessons.scss';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const form = useForm({
    name: '',
    description: '',
});

function submit() {
    form.post(route('admin.lessons.store'));
}
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb :items="[
                { label: 'Admin', href: route('admin.index') },
                { label: 'Lessons', href: route('admin.lessons.index') },
                { label: 'Create' },
            ]" />
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
                <section class="admin-card">
                    <h3 class="mb-4 text-base font-semibold text-gray-800 dark:text-gray-200">Lesson Details</h3>

                    <form @submit.prevent="submit" class="space-y-5">

                        <!-- Name -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Name
                            </label>
                            <input
                                id="name"
                                v-model="form.name"
                                type="text"
                                class="admin-form__input"
                                placeholder="Lesson name"
                                autofocus
                            />
                            <p v-if="form.errors.name" class="mt-1 text-xs text-red-500">{{ form.errors.name }}</p>
                        </div>

                        <!-- Description -->
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Description
                            </label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="4"
                                class="admin-form__textarea"
                                placeholder="Lesson description"
                            />
                            <p v-if="form.errors.description" class="mt-1 text-xs text-red-500">{{ form.errors.description }}</p>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end gap-3 pt-1">
                            <Link
                                :href="route('admin.lessons.index')"
                                class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400"
                            >Cancel</Link>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="admin-btn--primary"
                            >
                                {{ form.processing ? 'Creating…' : 'Create Lesson' }}
                            </button>
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
