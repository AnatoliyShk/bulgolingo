<script setup>
import '@/assets/scss/components/admin/bots.scss';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import Breadcrumb from '@/Components/Breadcrumb.vue';

defineProps({
    bots: Array,
});

function deleteBot(id) {
    if (confirm('Delete this bot?')) {
        router.delete(route('admin.bots.destroy', id));
    }
}
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="admin-page__header">
                <Breadcrumb :items="[
                    { label: 'Admin', href: route('admin.index') },
                    { label: 'Bots' },
                ]" />
                <Link :href="route('admin.bots.create')" class="admin-btn--new">+ New Bot</Link>
            </div>
        </template>

        <div class="admin-page__body">
            <div class="admin-page__container">
                <div v-if="bots.length === 0" class="admin-table__empty">No bots yet.</div>
                <div v-else class="admin-table__wrap">
                    <table class="admin-table__element">
                        <thead class="admin-table__head">
                            <tr>
                                <th class="admin-table__th">Name</th>
                                <th class="admin-table__th">Description</th>
                                <th class="admin-table__th">Dialogues</th>
                                <th class="admin-table__th">Created</th>
                                <th class="admin-table__th--action"></th>
                            </tr>
                        </thead>
                        <tbody class="admin-table__body">
                            <tr v-for="bot in bots" :key="bot.id">
                                <td class="admin-table__td--strong">{{ bot.name }}</td>
                                <td class="admin-table__td--truncate">{{ bot.description }}</td>
                                <td class="admin-table__td">{{ bot.scripted_dialogues_count }}</td>
                                <td class="admin-table__td">{{ new Date(bot.created_at).toLocaleDateString() }}</td>
                                <td class="admin-table__td--actions">
                                    <Link :href="route('admin.bots.edit', bot.id)" class="admin-btn--edit">Edit</Link>
                                    <button @click="deleteBot(bot.id)" class="admin-btn--delete">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
