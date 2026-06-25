<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link } from '@inertiajs/vue3';
import Breadcrumb from '@/Components/Breadcrumb.vue';
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb :items="[{ label: 'Admin Panel' }]" />
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-10 px-4 sm:px-6 lg:px-8">

                <!-- Learning Path -->
                <section>
                    <h2 class="mb-4 text-xs font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500">Learning Path</h2>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        <Link
                            :href="route('admin.learning-paths.index')"
                            class="block rounded-lg border border-gray-200 bg-white p-6 shadow hover:shadow-md dark:border-gray-700 dark:bg-gray-800"
                        >
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Learning Paths</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Create and manage learning paths with assigned lessons.
                            </p>
                        </Link>
                        <Link
                            :href="route('admin.lessons.index')"
                            class="block rounded-lg border border-gray-200 bg-white p-6 shadow hover:shadow-md dark:border-gray-700 dark:bg-gray-800"
                        >
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Lessons</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Manage all lessons and their exercises.
                            </p>
                        </Link>
                        <Link
                            :href="route('admin.exercises.index')"
                            class="block rounded-lg border border-gray-200 bg-white p-6 shadow hover:shadow-md dark:border-gray-700 dark:bg-gray-800"
                        >
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Exercises</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Browse all exercises across lessons.
                            </p>
                        </Link>
                    </div>
                </section>

                <!-- Bot Dialogue -->
                <section>
                    <h2 class="mb-4 text-xs font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500">Bot Dialogue</h2>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        <Link
                            :href="route('admin.bots.index')"
                            class="block rounded-lg border border-gray-200 bg-white p-6 shadow hover:shadow-md dark:border-gray-700 dark:bg-gray-800"
                        >
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Bots</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Manage dialogue bots and their descriptions.
                            </p>
                        </Link>
                        <Link
                            :href="route('admin.scripted-dialogues.index')"
                            class="block rounded-lg border border-gray-200 bg-white p-6 shadow hover:shadow-md dark:border-gray-700 dark:bg-gray-800"
                        >
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Scripted Dialogues</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Create and manage bot–user dialogue sessions.
                            </p>
                        </Link>
                        <Link
                            :href="route('admin.scripted-lines.index')"
                            class="block rounded-lg border border-gray-200 bg-white p-6 shadow hover:shadow-md dark:border-gray-700 dark:bg-gray-800"
                        >
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Scripted Lines</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Edit individual lines within a scripted dialogue.
                            </p>
                        </Link>
                    </div>
                </section>

                <!-- Users -->
                <section>
                    <h2 class="mb-4 text-xs font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500">Users</h2>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        <Link
                            :href="route('admin.users.index')"
                            class="block rounded-lg border border-gray-200 bg-white p-6 shadow hover:shadow-md dark:border-gray-700 dark:bg-gray-800"
                        >
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Users</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                View all registered users.
                            </p>
                        </Link>
                    </div>
                </section>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
