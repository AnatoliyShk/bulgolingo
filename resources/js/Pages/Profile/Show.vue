<!-- resources/js/Pages/Profile/Show.vue -->
<script setup>
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
const props = defineProps({
    user: Object,
    lessons: Array,
})

const page = usePage()
const lessons = computed(() => props.user.lessons)

function deleteLesson(id) {
    useForm({}).delete(route('lesson.destroy', id))
}

console.log(lessons.value)
</script>

<template>
    <div class="min-h-screen bg-gray-50 flex items-center justify-center p-6">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 w-full max-w-md p-8">

            <h1 class="text-xl font-semibold text-gray-900 text-center mb-6">Dashboard</h1>

            <div class="space-y-4">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-indigo-50 flex items-center justify-center">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Full name</p>
                        <p class="text-sm font-medium text-gray-800">{{ user.name }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-indigo-50 flex items-center justify-center">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Email</p>
                        <p class="text-sm font-medium text-gray-800">{{ user.email }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-indigo-50 flex items-center justify-center">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Member since</p>
                        <p class="text-sm font-medium text-gray-800">{{ user.created_at }}</p>
                    </div>
                </div>
            </div>

        </div>
        <div v-for="lesson in lessons" :key="lesson.id" class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-4">
            <Link :href="route('lesson.show', lesson.id)" class="text-sm font-medium text-gray-800">{{ lesson.description }}</Link>
            <p class="text-xs text-gray-400 mt-1">{{ lesson.name }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ lesson.created_at }}</p>
            <button
                type="button"
                @click="deleteLesson(lesson.id)"
                class="text-gray-300 hover:text-red-400 transition flex-shrink-0"
                title="Delete lesson"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M8 7V5a2 2 0 012-2h4a2 2 0 012 2v2" />
                </svg>
            </button>
        </div>
        <Link :href="route('lesson.create')" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">Create Lesson</Link>
    </div>
</template>
