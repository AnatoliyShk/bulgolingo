<script setup>
import '@/assets/scss/components/admin/learning-paths.scss';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
    types: { type: Array, required: true },
    levels: { type: Array, required: true },
});

const form = useForm({
    name: '',
    language: '',
    type: props.types[0]?.value ?? 'regular',
    level: null,
});

function submit() {
    form.post(route('admin.learning-paths.store'));
}
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb :items="[
                { label: 'Admin', href: route('admin.index') },
                { label: 'Learning Paths', href: route('admin.learning-paths.index') },
                { label: 'Create' },
            ]" />
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
                <section class="admin-card">
                    <h3 class="mb-4 text-base font-semibold text-gray-800 dark:text-gray-200">Path Details</h3>

                    <form @submit.prevent="submit" class="space-y-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
                            <input
                                v-model="form.name"
                                type="text"
                                class="admin-form__input"
                                placeholder="e.g. Beginner Bulgarian"
                                autofocus
                            />
                            <p v-if="form.errors.name" class="mt-1 text-xs text-red-500">{{ form.errors.name }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Language</label>
                            <input
                                v-model="form.language"
                                type="text"
                                class="admin-form__input"
                                placeholder="e.g. Bulgarian"
                            />
                            <p v-if="form.errors.language" class="mt-1 text-xs text-red-500">{{ form.errors.language }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                            <select
                                v-model="form.type"
                                class="admin-form__input"
                            >
                                <option v-for="option in types" :key="option.value" :value="option.value">{{ option.label }}</option>
                            </select>
                            <p v-if="form.errors.type" class="mt-1 text-xs text-red-500">{{ form.errors.type }}</p>
                        </div>

                        <div>
                            <label for="learning-path-level" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Level</label>
                            <select
                                id="learning-path-level"
                                v-model="form.level"
                                class="admin-form__input"
                            >
                                <option :value="null">Not set</option>
                                <option v-for="option in levels" :key="option.value" :value="option.value">{{ option.label }}</option>
                            </select>
                            <p v-if="form.errors.level" class="mt-1 text-xs text-red-500">{{ form.errors.level }}</p>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-1">
                            <Link
                                :href="route('admin.learning-paths.index')"
                                class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400"
                            >Cancel</Link>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="admin-btn--primary"
                            >
                                {{ form.processing ? 'Creating…' : 'Create Path' }}
                            </button>
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
