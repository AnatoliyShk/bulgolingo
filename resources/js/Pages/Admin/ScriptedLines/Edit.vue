<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
    line: Object,
    dialogues: Array,
});

const clause = props.line.clause ?? {};

const form = useForm({
    scripted_dialogue_id: props.line.scripted_dialogue_id,
    line_text: clause.line_text ?? '',
    options: clause.options?.length === 3 ? [...clause.options] : ['', '', ''],
    correct_option: clause.correct_option ?? 0,
});

function submit() {
    form.patch(route('admin.scripted-lines.update', props.line.id));
}

function dialogueLabel(d) {
    return `#${d.id} — ${d.bot?.name ?? 'unknown bot'}`;
}
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb :items="[
                { label: 'Admin', href: route('admin.index') },
                { label: 'Scripted Lines', href: route('admin.scripted-lines.index') },
                { label: `Line #${line.id}` },
            ]" />
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
                <section class="rounded-lg border border-gray-200 bg-white p-6 shadow dark:border-gray-700 dark:bg-gray-800">
                    <h3 class="mb-4 text-base font-semibold text-gray-800 dark:text-gray-200">Line Details</h3>

                    <form @submit.prevent="submit" class="space-y-5">

                        <!-- Dialogue -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Dialogue</label>
                            <select
                                v-model="form.scripted_dialogue_id"
                                class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-400 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                            >
                                <option v-for="d in dialogues" :key="d.id" :value="d.id">{{ dialogueLabel(d) }}</option>
                            </select>
                            <p v-if="form.errors.scripted_dialogue_id" class="mt-1 text-xs text-red-500">{{ form.errors.scripted_dialogue_id }}</p>
                        </div>

                        <!-- Line text -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Line text</label>
                            <input
                                v-model="form.line_text"
                                type="text"
                                class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-400 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                                placeholder="What does the bot say?"
                            />
                            <p v-if="form.errors.line_text" class="mt-1 text-xs text-red-500">{{ form.errors.line_text }}</p>
                        </div>

                        <!-- Options -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Options</label>
                            <div class="space-y-2">
                                <div v-for="(_, i) in form.options" :key="i" class="flex items-center gap-3">
                                    <input
                                        type="radio"
                                        :id="`correct_${i}`"
                                        :value="i"
                                        v-model="form.correct_option"
                                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-400"
                                        :title="`Mark option ${i + 1} as correct`"
                                    />
                                    <label :for="`option_${i}`" class="w-6 shrink-0 text-xs font-semibold text-gray-500 dark:text-gray-400">{{ i + 1 }}</label>
                                    <input
                                        :id="`option_${i}`"
                                        v-model="form.options[i]"
                                        type="text"
                                        class="flex-1 rounded-lg border px-4 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-400 dark:bg-gray-900 dark:text-gray-100"
                                        :class="form.correct_option === i
                                            ? 'border-indigo-400 dark:border-indigo-500'
                                            : 'border-gray-300 dark:border-gray-600'"
                                        :placeholder="`Option ${i + 1}`"
                                    />
                                </div>
                            </div>
                            <p class="mt-1.5 text-xs text-gray-400 dark:text-gray-500">Select the radio button next to the correct answer.</p>
                            <p v-if="form.errors.options" class="mt-1 text-xs text-red-500">{{ form.errors.options }}</p>
                            <p v-if="form.errors.correct_option" class="mt-1 text-xs text-red-500">{{ form.errors.correct_option }}</p>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-1">
                            <Link
                                :href="route('admin.scripted-lines.index')"
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
