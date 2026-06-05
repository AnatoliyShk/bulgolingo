<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Admin Panel
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <Link
                        :href="route('admin.lessons.index')"
                        class="block rounded-lg border border-gray-200 bg-white p-6 shadow hover:shadow-md dark:border-gray-700 dark:bg-gray-800"
                    >
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Lessons</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Manage all lessons and their exercises.
                        </p>
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
