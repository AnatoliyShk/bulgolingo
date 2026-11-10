<script setup>
import '@/assets/scss/components/admin/learning-paths.scss';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { router } from '@inertiajs/vue3';
import { onBeforeUnmount, ref, watch } from 'vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import Pagination from '@/Components/Admin/Pagination.vue';
import LearningPathForm from '@/Components/Forms/LearningPathForm.vue';

const props = defineProps({
    learningPath: Object,
    lessons: Object,
    lessonSearch: String,
    types: Array,
    levels: Array,
});

const lessonSearch = ref(props.lessonSearch ?? '');

// Only the lessons prop is re-requested, and the component is kept alive, so
// searching or turning a page leaves selections the admin has not saved intact.
let searchTimer = null;

watch(lessonSearch, (value) => {
    clearTimeout(searchTimer);

    searchTimer = setTimeout(() => {
        router.get(
            route('admin.learning-paths.edit', props.learningPath.id),
            { lesson_search: value, lesson_page: 1 },
            { only: ['lessons'], preserveState: true, preserveScroll: true, replace: true },
        );
    }, 300);
});

onBeforeUnmount(() => clearTimeout(searchTimer));

function toggleLesson(form, id) {
    if (form.lesson_ids.includes(id)) {
        form.lesson_ids = form.lesson_ids.filter(i => i !== id);
    } else {
        form.lesson_ids = [...form.lesson_ids, id];
    }
}
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb :items="[
                { label: 'Admin', href: route('admin.index') },
                { label: 'Learning Paths', href: route('admin.learning-paths.index') },
                { label: learningPath.name },
            ]" />
        </template>

        <div class="admin-page__body">
            <div class="admin-page__container--medium">
                <section class="admin-card">
                    <h3 class="admin-card__title">Path Details</h3>

                    <LearningPathForm :learning-path="learningPath" :types="types" :levels="levels" v-slot="{ form }">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label for="lesson-search" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Lessons</label>
                                <span class="text-xs text-gray-400 dark:text-gray-500">{{ form.lesson_ids.length }} selected</span>
                            </div>
                            <input
                                id="lesson-search"
                                v-model="lessonSearch"
                                type="text"
                                placeholder="Search lessons…"
                                class="admin-path__lesson-search"
                            />
                            <div v-if="lessons.total === 0 && lessonSearch.trim()" class="py-2 text-sm text-gray-400">No lessons match your search.</div>
                            <div v-else-if="lessons.total === 0" class="text-sm text-gray-400">No lessons available.</div>
                            <div v-else class="rounded-lg border border-gray-200 dark:border-gray-600">
                                <ul class="divide-y divide-gray-100 dark:divide-gray-700" data-testid="lesson-picker">
                                    <li
                                        v-for="lesson in lessons.data"
                                        :key="lesson.id"
                                        class="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 dark:hover:bg-gray-700/40 cursor-pointer"
                                        @click="toggleLesson(form, lesson.id)"
                                    >
                                        <div
                                            class="w-4 h-4 rounded border-2 flex items-center justify-center flex-shrink-0 transition-colors"
                                            :class="form.lesson_ids.includes(lesson.id)
                                                ? 'bg-indigo-600 border-indigo-600'
                                                : 'border-gray-300 dark:border-gray-500'"
                                        >
                                            <svg v-if="form.lesson_ids.includes(lesson.id)" class="w-2.5 h-2.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ lesson.name }}</p>
                                            <p v-if="lesson.description" class="text-xs text-gray-400">{{ lesson.description }}</p>
                                        </div>
                                    </li>
                                </ul>

                                <Pagination :paginator="lessons" :only="['lessons']" preserve-state />
                            </div>
                            <p v-if="form.errors.lesson_ids" class="mt-1 text-xs text-red-500">{{ form.errors.lesson_ids }}</p>
                        </div>
                    </LearningPathForm>
                </section>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
