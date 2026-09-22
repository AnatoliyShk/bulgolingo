<script setup>
import '@/assets/scss/components/admin/settings.scss';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { useTheme } from '@/composables/useTheme';
import Breadcrumb from '@/Components/Breadcrumb.vue';

useTheme();

const page = usePage();

const form = useForm({
    embedding_search_enabled: page.props.settings.embedding_search_enabled,
    embedding_min_similarity: page.props.settings.embedding_min_similarity,
    tutor_bot_enabled: page.props.settings.tutor_bot_enabled,
});

function toggleSearch() {
    form.embedding_search_enabled = !form.embedding_search_enabled;
}

function toggleTutor() {
    form.tutor_bot_enabled = !form.tutor_bot_enabled;
}

function submit() {
    form.put(route('admin.settings.update'), { preserveScroll: true });
}
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb :items="[
                { label: 'Admin', href: route('admin.index') },
                { label: 'Settings' },
            ]" />
        </template>

        <div class="admin-page__body">
            <div class="admin-page__container--narrow">
                <form @submit.prevent="submit">
                    <section class="admin-card">
                        <h3 class="admin-card__title">Embedding search</h3>

                        <div class="admin-form__body">
                            <div class="admin-settings__row">
                                <div>
                                    <p id="embedding-search-label" class="admin-settings__name">Search learning paths by meaning</p>
                                    <p id="embedding-search-desc" class="admin-settings__desc">
                                        When off, the search field is hidden from the learning paths page and nothing is sent
                                        to Gemini: no searches, and no embeddings generated for exercises.
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    role="switch"
                                    class="admin-settings__switch"
                                    :class="{ 'admin-settings__switch--on': form.embedding_search_enabled }"
                                    :aria-checked="form.embedding_search_enabled"
                                    aria-labelledby="embedding-search-label"
                                    aria-describedby="embedding-search-desc"
                                    @click="toggleSearch"
                                >
                                    <span class="admin-settings__knob" aria-hidden="true" />
                                </button>
                            </div>
                            <p v-if="form.errors.embedding_search_enabled" class="admin-form__error">{{ form.errors.embedding_search_enabled }}</p>

                            <div class="admin-form__field">
                                <label for="embedding-min-similarity" class="admin-form__label">Minimum similarity</label>
                                <input
                                    id="embedding-min-similarity"
                                    v-model.number="form.embedding_min_similarity"
                                    type="number"
                                    min="0"
                                    max="1"
                                    step="0.01"
                                    inputmode="decimal"
                                    class="admin-form__input admin-settings__number"
                                    :aria-invalid="!!form.errors.embedding_min_similarity"
                                    aria-describedby="embedding-min-similarity-hint"
                                />
                                <p v-if="form.errors.embedding_min_similarity" class="admin-form__error">{{ form.errors.embedding_min_similarity }}</p>
                                <p id="embedding-min-similarity-hint" class="admin-form__hint">
                                    From 0 to 1. A path is shown only if one of its exercises scores at least this close to the
                                    search. With gemini-embedding-2, unrelated exercises score about 0.5 and real matches 0.6 and up.
                                </p>
                            </div>
                        </div>
                    </section>

                    <section class="admin-card">
                        <h3 class="admin-card__title">Tutor bot</h3>

                        <div class="admin-form__body">
                            <div class="admin-settings__row">
                                <div>
                                    <p id="tutor-bot-label" class="admin-settings__name">Answer questions on the welcome page</p>
                                    <p id="tutor-bot-desc" class="admin-settings__desc">
                                        Puts a small tutor on the public home page that answers questions about learning
                                        Bulgarian and recommends paths from the catalog. Every answer is a paid completion
                                        and visitors need no account, so leave this off unless you are watching the spend.
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    role="switch"
                                    class="admin-settings__switch"
                                    :class="{ 'admin-settings__switch--on': form.tutor_bot_enabled }"
                                    :aria-checked="form.tutor_bot_enabled"
                                    aria-labelledby="tutor-bot-label"
                                    aria-describedby="tutor-bot-desc"
                                    @click="toggleTutor"
                                >
                                    <span class="admin-settings__knob" aria-hidden="true" />
                                </button>
                            </div>
                            <p v-if="form.errors.tutor_bot_enabled" class="admin-form__error">{{ form.errors.tutor_bot_enabled }}</p>
                        </div>
                    </section>

                    <div class="admin-form__actions">
                        <p v-if="form.recentlySuccessful" class="admin-settings__saved" role="status">Saved.</p>
                        <button type="submit" :disabled="form.processing" class="admin-btn--primary">
                            {{ form.processing ? 'Saving…' : 'Save' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
