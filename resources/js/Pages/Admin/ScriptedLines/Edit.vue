<script setup>
import '@/assets/scss/components/admin/scripted-lines.scss';
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

        <div class="admin-page__body">
            <div class="admin-page__container--narrow">
                <section class="admin-card">
                    <h3 class="admin-card__title">Line Details</h3>

                    <form @submit.prevent="submit" class="admin-form__body">

                        <div class="admin-form__field">
                            <label class="admin-form__label">Dialogue</label>
                            <select v-model="form.scripted_dialogue_id" class="admin-form__select">
                                <option v-for="d in dialogues" :key="d.id" :value="d.id">{{ dialogueLabel(d) }}</option>
                            </select>
                            <p v-if="form.errors.scripted_dialogue_id" class="admin-form__error">{{ form.errors.scripted_dialogue_id }}</p>
                        </div>

                        <div class="admin-form__field">
                            <label class="admin-form__label">Line text</label>
                            <input
                                v-model="form.line_text"
                                type="text"
                                class="admin-form__input"
                                placeholder="What does the bot say?"
                            />
                            <p v-if="form.errors.line_text" class="admin-form__error">{{ form.errors.line_text }}</p>
                        </div>

                        <div class="admin-form__field">
                            <label class="admin-form__label--spaced">Options</label>
                            <div class="scripted-line-form__options">
                                <div v-for="(_, i) in form.options" :key="i" class="scripted-line-form__option-row">
                                    <input
                                        type="radio"
                                        :id="`correct_${i}`"
                                        :value="i"
                                        v-model="form.correct_option"
                                        class="scripted-line-form__option-radio"
                                        :title="`Mark option ${i + 1} as correct`"
                                    />
                                    <label :for="`option_${i}`" class="scripted-line-form__option-num">{{ i + 1 }}</label>
                                    <input
                                        :id="`option_${i}`"
                                        v-model="form.options[i]"
                                        type="text"
                                        class="scripted-line-form__option-input"
                                        :class="form.correct_option === i
                                            ? 'scripted-line-form__option-input--active'
                                            : 'scripted-line-form__option-input--inactive'"
                                        :placeholder="`Option ${i + 1}`"
                                    />
                                </div>
                            </div>
                            <p class="admin-form__hint">Select the radio button next to the correct answer.</p>
                            <p v-if="form.errors.options" class="admin-form__error">{{ form.errors.options }}</p>
                            <p v-if="form.errors.correct_option" class="admin-form__error">{{ form.errors.correct_option }}</p>
                        </div>

                        <div class="admin-form__actions">
                            <Link :href="route('admin.scripted-lines.index')" class="admin-btn--cancel">Cancel</Link>
                            <button type="submit" :disabled="form.processing" class="admin-btn--primary">
                                {{ form.processing ? 'Saving…' : 'Save' }}
                            </button>
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
