<script setup>
import '@/assets/scss/components/admin/panel.scss';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link } from '@inertiajs/vue3';
import Breadcrumb from '@/Components/Breadcrumb.vue';
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb :items="[{ label: 'Admin Panel' }]" />
        </template>

        <div class="admin-panel__body">
            <div class="admin-panel__container">

                <section>
                    <h2 class="admin-panel__section-label">Learning Path</h2>
                    <div class="admin-panel__grid">
                        <Link :href="route('admin.learning-paths.index')" class="admin-panel__card">
                            <h3 class="admin-panel__card-title">Learning Paths</h3>
                            <p class="admin-panel__card-desc">Create and manage learning paths with assigned lessons.</p>
                        </Link>
                        <Link :href="route('admin.lessons.index')" class="admin-panel__card">
                            <h3 class="admin-panel__card-title">Lessons</h3>
                            <p class="admin-panel__card-desc">Manage all lessons and their exercises.</p>
                        </Link>
                        <Link :href="route('admin.exercises.index')" class="admin-panel__card">
                            <h3 class="admin-panel__card-title">Exercises</h3>
                            <p class="admin-panel__card-desc">Browse all exercises across lessons.</p>
                        </Link>
                    </div>
                </section>

                <section>
                    <h2 class="admin-panel__section-label">Bot Dialogue</h2>
                    <div class="admin-panel__grid">
                        <Link :href="route('admin.bots.index')" class="admin-panel__card">
                            <h3 class="admin-panel__card-title">Bots</h3>
                            <p class="admin-panel__card-desc">Manage dialogue bots and their descriptions.</p>
                        </Link>
                        <Link :href="route('admin.scripted-dialogues.index')" class="admin-panel__card">
                            <h3 class="admin-panel__card-title">Scripted Dialogues</h3>
                            <p class="admin-panel__card-desc">Create and manage bot–user dialogue sessions.</p>
                        </Link>
                        <Link :href="route('admin.scripted-lines.index')" class="admin-panel__card">
                            <h3 class="admin-panel__card-title">Scripted Lines</h3>
                            <p class="admin-panel__card-desc">Edit individual lines within a scripted dialogue.</p>
                        </Link>
                    </div>
                </section>

                <section>
                    <h2 class="admin-panel__section-label">Users</h2>
                    <div class="admin-panel__grid">
                        <Link :href="route('admin.users.index')" class="admin-panel__card">
                            <h3 class="admin-panel__card-title">Users</h3>
                            <p class="admin-panel__card-desc">View all registered users.</p>
                        </Link>
                    </div>
                </section>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
