<script setup>
import '@/assets/scss/components/admin/users.scss';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

defineProps({
    users: Array,
});
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb :items="[
                { label: 'Admin', href: route('admin.index') },
                { label: 'Users' },
            ]" />
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div v-if="users.length === 0" class="text-gray-500 dark:text-gray-400">No users yet.</div>

                <div v-else class="admin-table__wrap">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="admin-table__th">Name</th>
                                <th class="admin-table__th">Email</th>
                                <th class="admin-table__th">Role</th>
                                <th class="admin-table__th">Verified</th>
                                <th class="admin-table__th">Joined</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="user in users" :key="user.id">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">{{ user.name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ user.email }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    <span
                                        v-if="user.role.name !== 'student'"
                                        class="admin-badge admin-badge--role"
                                    >{{ user.role.label }}</span>
                                    <span v-else>{{ user.role.label }}</span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    <span
                                        v-if="user.email_verified_at"
                                        class="admin-badge admin-badge--verified"
                                    >Verified</span>
                                    <span
                                        v-else
                                        class="admin-badge admin-badge--unverified"
                                    >Unverified</span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ new Date(user.created_at).toLocaleDateString() }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
