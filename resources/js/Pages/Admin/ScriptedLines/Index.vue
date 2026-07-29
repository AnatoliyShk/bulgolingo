<script setup>
import '@/assets/scss/components/admin/scripted-lines.scss';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import Breadcrumb from '@/Components/Breadcrumb.vue';

defineProps({
    lines: Array,
});

function deleteLine(id) {
    if (confirm('Delete this line?')) {
        router.delete(route('admin.scripted-lines.destroy', id));
    }
}
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="admin-page__header">
                <Breadcrumb :items="[
                    { label: 'Admin', href: route('admin.index') },
                    { label: 'Scripted Lines' },
                ]" />
                <Link :href="route('admin.scripted-lines.create')" class="admin-btn--new">+ New Line</Link>
            </div>
        </template>

        <div class="admin-page__body">
            <div class="admin-page__container">
                <div v-if="lines.length === 0" class="admin-table__empty">No scripted lines yet.</div>
                <div v-else class="admin-table__wrap">
                    <table class="admin-table__element">
                        <thead class="admin-table__head">
                            <tr>
                                <th class="admin-table__th">#</th>
                                <th class="admin-table__th">Dialogue</th>
                                <th class="admin-table__th">Bot</th>
                                <th class="admin-table__th">Line text</th>
                                <th class="admin-table__th">Created</th>
                                <th class="admin-table__th--action"></th>
                            </tr>
                        </thead>
                        <tbody class="admin-table__body">
                            <tr v-for="line in lines" :key="line.id">
                                <td class="admin-table__td">{{ line.id }}</td>
                                <td class="admin-table__td">#{{ line.scripted_dialogue_id }}</td>
                                <td class="admin-table__td--strong">{{ line.dialogue?.bot?.name ?? '—' }}</td>
                                <td class="admin-table__td--truncate">{{ line.clause?.line_text ?? '—' }}</td>
                                <td class="admin-table__td">{{ new Date(line.created_at).toLocaleDateString() }}</td>
                                <td class="admin-table__td--actions">
                                    <Link :href="route('admin.scripted-lines.edit', line.id)" class="admin-btn--edit">Edit</Link>
                                    <button @click="deleteLine(line.id)" class="admin-btn--delete">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
