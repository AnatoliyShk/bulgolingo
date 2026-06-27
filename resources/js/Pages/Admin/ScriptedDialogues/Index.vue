<script setup>
import '@/assets/scss/components/admin/scripted-dialogues.scss';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import Breadcrumb from '@/Components/Breadcrumb.vue';

defineProps({
    dialogues: Array,
});

function deleteDialogue(id) {
    if (confirm('Delete this dialogue? All its lines will also be deleted.')) {
        router.delete(route('admin.scripted-dialogues.destroy', id));
    }
}
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="admin-page__header">
                <Breadcrumb :items="[
                    { label: 'Admin', href: route('admin.index') },
                    { label: 'Scripted Dialogues' },
                ]" />
                <Link :href="route('admin.scripted-dialogues.create')" class="admin-btn--new">+ New Dialogue</Link>
            </div>
        </template>

        <div class="admin-page__body">
            <div class="admin-page__container">
                <div v-if="dialogues.length === 0" class="admin-table__empty">No scripted dialogues yet.</div>
                <div v-else class="admin-table__wrap">
                    <table class="admin-table__element">
                        <thead class="admin-table__head">
                            <tr>
                                <th class="admin-table__th">#</th>
                                <th class="admin-table__th">Bot</th>
                                <th class="admin-table__th">User ID</th>
                                <th class="admin-table__th">Lines</th>
                                <th class="admin-table__th">Created</th>
                                <th class="admin-table__th--action"></th>
                            </tr>
                        </thead>
                        <tbody class="admin-table__body">
                            <tr v-for="dialogue in dialogues" :key="dialogue.id">
                                <td class="admin-table__td">{{ dialogue.id }}</td>
                                <td class="admin-table__td--strong">{{ dialogue.bot?.name ?? '—' }}</td>
                                <td class="admin-table__td">{{ dialogue.user_id }}</td>
                                <td class="admin-table__td">{{ dialogue.lines_count }}</td>
                                <td class="admin-table__td">{{ new Date(dialogue.created_at).toLocaleDateString() }}</td>
                                <td class="admin-table__td--actions">
                                    <Link :href="route('admin.scripted-dialogues.edit', dialogue.id)" class="admin-btn--edit">Edit</Link>
                                    <button @click="deleteDialogue(dialogue.id)" class="admin-btn--delete">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
