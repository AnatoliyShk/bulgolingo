<script setup>
import '@/assets/scss/components/admin/messengers.scss';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import Breadcrumb from '@/Components/Breadcrumb.vue';

defineProps({
    messengers: Array,
});

function deleteMessenger(id) {
    if (confirm('Delete this messenger?')) {
        router.delete(route('admin.messengers.destroy', id));
    }
}
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="admin-page__header">
                <Breadcrumb :items="[
                    { label: 'Admin', href: route('admin.index') },
                    { label: 'Messengers' },
                ]" />
                <Link :href="route('admin.messengers.create')" class="admin-btn--new">+ New Messenger</Link>
            </div>
        </template>

        <div class="admin-page__body">
            <div class="admin-page__container">
                <div v-if="messengers.length === 0" class="admin-table__empty">No messengers yet.</div>
                <div v-else class="admin-table__wrap">
                    <table class="admin-table__element">
                        <thead class="admin-table__head">
                            <tr>
                                <th class="admin-table__th">User</th>
                                <th class="admin-table__th">Messenger</th>
                                <th class="admin-table__th">Messenger User ID</th>
                                <th class="admin-table__th">Created</th>
                                <th class="admin-table__th--action"></th>
                            </tr>
                        </thead>
                        <tbody class="admin-table__body">
                            <tr v-for="messenger in messengers" :key="messenger.id">
                                <td class="admin-table__td--strong">{{ messenger.user?.name }}</td>
                                <td class="admin-table__td">{{ messenger.messenger_name }}</td>
                                <td class="admin-table__td">{{ messenger.messenger_user_id }}</td>
                                <td class="admin-table__td">{{ new Date(messenger.created_at).toLocaleDateString() }}</td>
                                <td class="admin-table__td--actions">
                                    <Link :href="route('admin.messengers.edit', messenger.id)" class="admin-btn--edit">Edit</Link>
                                    <button @click="deleteMessenger(messenger.id)" class="admin-btn--delete">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
