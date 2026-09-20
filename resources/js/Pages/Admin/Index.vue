<script setup>
import '@/assets/scss/components/admin/panel.scss';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const page = usePage();
const isAdminVisitor = computed(() => page.props.auth.isAdminVisitor);
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb :items="[{ label: 'Admin Panel' }]" />
        </template>

        <div class="admin-panel__body">
            <div class="admin-panel__container">

                <p v-if="isAdminVisitor" class="admin-panel__notice">
                    Read-only visitor access: you can browse admin pages, but user
                    records are hidden and no changes can be saved.
                </p>

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

                <section v-if="!isAdminVisitor">
                    <h2 class="admin-panel__section-label">Users</h2>
                    <div class="admin-panel__grid">
                        <Link :href="route('admin.users.index')" class="admin-panel__card">
                            <h3 class="admin-panel__card-title">Users</h3>
                            <p class="admin-panel__card-desc">View all registered users.</p>
                        </Link>
                        <Link :href="route('admin.messengers.index')" class="admin-panel__card">
                            <h3 class="admin-panel__card-title">Messengers</h3>
                            <p class="admin-panel__card-desc">Link user accounts to their messenger identifiers.</p>
                        </Link>
                    </div>
                </section>

                <section>
                    <h2 class="admin-panel__section-label">Configuration</h2>
                    <div class="admin-panel__grid">
                        <Link :href="route('admin.settings.edit')" class="admin-panel__card">
                            <h3 class="admin-panel__card-title">Settings</h3>
                            <p class="admin-panel__card-desc">Turn embedding search on or off and set its minimum similarity.</p>
                        </Link>
                    </div>
                </section>

                <section>
                    <h2 class="admin-panel__section-label">Monitoring</h2>
                    <div class="admin-panel__grid">
                        <Link :href="route('admin.metrics.admin')" class="admin-panel__card">
                            <h3 class="admin-panel__card-title">Admin Request Metrics</h3>
                            <p class="admin-panel__card-desc">HTTP request duration and slow requests for the admin panel.</p>
                        </Link>
                        <Link :href="route('admin.metrics.user')" class="admin-panel__card">
                            <h3 class="admin-panel__card-title">User Request Metrics</h3>
                            <p class="admin-panel__card-desc">HTTP request duration and slow requests for everything else.</p>
                        </Link>
                        <Link :href="route('admin.vitals.index')" class="admin-panel__card">
                            <h3 class="admin-panel__card-title">Web Vitals</h3>
                            <p class="admin-panel__card-desc">View LCP, INP, CLS, and TTFB field data from real visits.</p>
                        </Link>
                        <a href="/admin/logs" class="admin-panel__card">
                            <h3 class="admin-panel__card-title">Logs</h3>
                            <p class="admin-panel__card-desc">Browse application logs, filterable by level.</p>
                        </a>
                        <a href="/telescope" class="admin-panel__card">
                            <h3 class="admin-panel__card-title">Telescope</h3>
                            <p class="admin-panel__card-desc">Inspect requests, queries, jobs, and exceptions in detail.</p>
                        </a>
                    </div>
                </section>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
