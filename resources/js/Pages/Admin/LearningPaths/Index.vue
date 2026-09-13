<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import Pagination from '@/Components/Admin/Pagination.vue';

const props = defineProps({
    learningPaths: Object,
    levels: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({ level: null }) },
});

const level = ref(props.filters.level ?? '');

// A new filter starts again from the first page, since the page the admin was
// on may not exist in the narrower list. The empty value asks for every level.
function filterByLevel() {
    router.get(
        route('admin.learning-paths.index'),
        level.value ? { level: level.value } : {},
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function deletePath(id) {
    if (confirm('Delete this learning path?')) {
        router.delete(route('admin.learning-paths.destroy', id));
    }
}
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <Breadcrumb :items="[
                    { label: 'Admin', href: route('admin.index') },
                    { label: 'Learning Paths' },
                ]" />
                <Link
                    :href="route('admin.learning-paths.create')"
                    class="rounded bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700"
                >
                    + New Path
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-4 flex items-center gap-3">
                    <label for="learning-path-level-filter" class="text-sm font-medium text-gray-700 dark:text-gray-300">Level</label>
                    <select
                        id="learning-path-level-filter"
                        v-model="level"
                        class="rounded-lg border border-gray-300 px-3 py-1.5 pr-8 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-400 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                        @change="filterByLevel"
                    >
                        <option value="">All levels</option>
                        <option value="none">Not set</option>
                        <option v-for="option in levels" :key="option.value" :value="option.value">{{ option.label }}</option>
                    </select>
                </div>

                <div v-if="learningPaths.total === 0" class="text-gray-500 dark:text-gray-400">
                    {{ filters.level === 'none' ? 'Every learning path has a level.' : filters.level ? 'No learning paths at this level.' : 'No learning paths yet.' }}
                </div>

                <div v-else class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow dark:border-gray-700 dark:bg-gray-800">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Language</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Level</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Lessons</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Created</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="path in learningPaths.data" :key="path.id">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">{{ path.name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ path.language }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400" data-testid="learning-path-level">{{ path.level ?? 'Not set' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ path.lessons_count }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ new Date(path.created_at).toLocaleDateString() }}</td>
                                <td class="px-6 py-4 text-right text-sm">
                                    <Link
                                        :href="route('admin.learning-paths.edit', path.id)"
                                        class="mr-3 text-blue-600 hover:underline dark:text-blue-400"
                                    >Edit</Link>
                                    <button
                                        @click="deletePath(path.id)"
                                        class="text-red-600 hover:underline dark:text-red-400"
                                    >Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <Pagination :paginator="learningPaths" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
