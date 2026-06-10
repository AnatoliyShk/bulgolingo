<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    learningPath: Object,
    lessons: Array,
});

const form = useForm({
    name: props.learningPath.name,
    language: props.learningPath.language,
    lesson_ids: props.learningPath.lessons.map(l => l.id),
});

const attachedIds = computed(() => new Set(form.lesson_ids));

function toggleLesson(id) {
    if (attachedIds.value.has(id)) {
        form.lesson_ids = form.lesson_ids.filter(i => i !== id);
    } else {
        form.lesson_ids = [...form.lesson_ids, id];
    }
}

function submit() {
    form.put(route('admin.learning-paths.update', props.learningPath.id));
}
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link
                    :href="route('admin.learning-paths.index')"
                    class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                >← Learning Paths</Link>
                <span class="text-gray-300 dark:text-gray-600">/</span>
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Edit Learning Path
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl space-y-8 px-4 sm:px-6 lg:px-8">

                <!-- Path details -->
                <section class="rounded-lg border border-gray-200 bg-white p-6 shadow dark:border-gray-700 dark:bg-gray-800">
                    <h3 class="mb-4 text-base font-semibold text-gray-800 dark:text-gray-200">Path Details</h3>

                    <form @submit.prevent="submit" class="space-y-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
                            <input
                                v-model="form.name"
                                type="text"
                                class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-400 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                            />
                            <p v-if="form.errors.name" class="mt-1 text-xs text-red-500">{{ form.errors.name }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Language</label>
                            <input
                                v-model="form.language"
                                type="text"
                                class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-400 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                            />
                            <p v-if="form.errors.language" class="mt-1 text-xs text-red-500">{{ form.errors.language }}</p>
                        </div>

                        <!-- Lessons picker -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Lessons</label>
                            <div v-if="lessons.length === 0" class="text-sm text-gray-400">No lessons available.</div>
                            <ul v-else class="divide-y divide-gray-100 rounded-lg border border-gray-200 dark:divide-gray-700 dark:border-gray-600">
                                <li
                                    v-for="lesson in lessons"
                                    :key="lesson.id"
                                    class="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 dark:hover:bg-gray-700/40 cursor-pointer"
                                    @click="toggleLesson(lesson.id)"
                                >
                                    <div
                                        class="w-4 h-4 rounded border-2 flex items-center justify-center flex-shrink-0 transition-colors"
                                        :class="attachedIds.has(lesson.id)
                                            ? 'bg-indigo-600 border-indigo-600'
                                            : 'border-gray-300 dark:border-gray-500'"
                                    >
                                        <svg v-if="attachedIds.has(lesson.id)" class="w-2.5 h-2.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ lesson.name }}</p>
                                        <p v-if="lesson.description" class="text-xs text-gray-400">{{ lesson.description }}</p>
                                    </div>
                                </li>
                            </ul>
                            <p v-if="form.errors.lesson_ids" class="mt-1 text-xs text-red-500">{{ form.errors.lesson_ids }}</p>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-1">
                            <Link
                                :href="route('admin.learning-paths.index')"
                                class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400"
                            >Cancel</Link>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50 transition"
                            >
                                {{ form.processing ? 'Saving…' : 'Save' }}
                            </button>
                        </div>
                    </form>
                </section>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
