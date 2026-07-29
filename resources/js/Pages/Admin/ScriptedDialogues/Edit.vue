<script setup>
import '@/assets/scss/components/admin/scripted-dialogues.scss';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link, useForm, router } from '@inertiajs/vue3';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
    dialogue: Object,
    bots: Array,
    users: Array,
});

const form = useForm({
    bot_id: props.dialogue.bot_id,
    user_id: props.dialogue.user_id,
});

function submit() {
    form.patch(route('admin.scripted-dialogues.update', props.dialogue.id));
}

function deleteLine(id) {
    if (confirm('Delete this line?')) {
        router.delete(route('admin.scripted-lines.destroy', id));
    }
}
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb :items="[
                { label: 'Admin', href: route('admin.index') },
                { label: 'Scripted Dialogues', href: route('admin.scripted-dialogues.index') },
                { label: `Dialogue #${dialogue.id}` },
            ]" />
        </template>

        <div class="admin-page__body">
            <div class="admin-page__container--medium">

                <section class="admin-card">
                    <h3 class="admin-card__title">Dialogue Details</h3>

                    <form @submit.prevent="submit" class="admin-form__body">
                        <div class="admin-form__field">
                            <label class="admin-form__label">Bot</label>
                            <select v-model="form.bot_id" class="admin-form__select">
                                <option v-for="bot in bots" :key="bot.id" :value="bot.id">{{ bot.name }}</option>
                            </select>
                            <p v-if="form.errors.bot_id" class="admin-form__error">{{ form.errors.bot_id }}</p>
                        </div>

                        <div class="admin-form__field">
                            <label class="admin-form__label">User</label>
                            <select v-model="form.user_id" class="admin-form__select">
                                <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
                            </select>
                            <p v-if="form.errors.user_id" class="admin-form__error">{{ form.errors.user_id }}</p>
                        </div>

                        <div class="admin-form__actions">
                            <button type="submit" :disabled="form.processing" class="admin-btn--primary">
                                {{ form.processing ? 'Saving…' : 'Save' }}
                            </button>
                        </div>
                    </form>
                </section>

                <section class="admin-card">
                    <div class="scripted-dialogue-edit__lines-header">
                        <h3 class="scripted-dialogue-edit__lines-title">
                            Lines
                            <span class="scripted-dialogue-edit__lines-count">({{ dialogue.lines.length }})</span>
                        </h3>
                        <Link
                            :href="route('admin.scripted-lines.create', { scripted_dialogue_id: dialogue.id })"
                            class="admin-btn--link-sm"
                        >+ Add Line</Link>
                    </div>

                    <div v-if="dialogue.lines.length === 0" class="scripted-dialogue-edit__lines-empty">
                        No lines yet.
                    </div>

                    <ul class="scripted-dialogue-edit__lines-list">
                        <li v-for="line in dialogue.lines" :key="line.id" class="scripted-dialogue-edit__line">
                            <div class="scripted-dialogue-edit__line-row">
                                <p class="scripted-dialogue-edit__line-text">{{ line.clause?.line_text ?? '—' }}</p>
                                <div class="scripted-dialogue-edit__line-actions">
                                    <Link :href="route('admin.scripted-lines.edit', line.id)" class="admin-btn--edit-sm">Edit</Link>
                                    <button type="button" @click="deleteLine(line.id)" class="admin-btn--delete-sm">Delete</button>
                                </div>
                            </div>
                        </li>
                    </ul>
                </section>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
