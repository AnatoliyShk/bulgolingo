<script setup>
import '@/assets/scss/components/admin/_admin-marquee-words-form.scss'
import { computed } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import { useTheme } from '@/composables/useTheme'

useTheme()

const page = usePage()

const form = useForm({
    words: (page.props.marqueeWords ?? []).map((word) => ({ bg: word.bg, en: word.en })),
})

const canRemove = computed(() => form.words.length > 1)

// Appends a blank pair at the end of the list, the place the new word will
// appear in the running line.
function addWord() {
    form.words.push({ bg: '', en: '' })
}

// Drops one pair, but never the last: the server refuses an empty list, so the
// button is disabled instead of letting the form reach that error.
function removeWord(index) {
    if (!canRemove.value) return

    form.words.splice(index, 1)
}

// Saves the whole list in one request. The server answers with a redirect back
// to the settings page, whose marqueeWords prop then matches what was saved.
function submit() {
    form.put(route('admin.settings.marquee'), { preserveScroll: true })
}
</script>

<template>
    <form class="admin-marquee-form" @submit.prevent="submit">
        <section class="admin-card">
            <h3 class="admin-card__title">Running line</h3>

            <div class="admin-form__body">
                <p id="marquee-words-desc" class="admin-marquee-form__desc">
                    The Bulgarian words and their English meanings that scroll across the welcome page. They
                    play in the order listed here.
                </p>

                <ul class="admin-marquee-form__list" aria-describedby="marquee-words-desc">
                    <li v-for="(word, i) in form.words" :key="i" class="admin-marquee-form__row">
                        <span class="admin-marquee-form__num">{{ i + 1 }}</span>
                        <div class="admin-marquee-form__pair">
                            <input
                                v-model="word.bg"
                                type="text"
                                lang="bg"
                                maxlength="60"
                                class="admin-form__input"
                                :aria-label="`Bulgarian word ${i + 1}`"
                                :aria-invalid="!!form.errors[`words.${i}.bg`]"
                            />
                            <input
                                v-model="word.en"
                                type="text"
                                maxlength="60"
                                class="admin-form__input"
                                :aria-label="`English meaning ${i + 1}`"
                                :aria-invalid="!!form.errors[`words.${i}.en`]"
                            />
                            <p v-if="form.errors[`words.${i}.bg`]" class="admin-form__error">{{ form.errors[`words.${i}.bg`] }}</p>
                            <p v-if="form.errors[`words.${i}.en`]" class="admin-form__error">{{ form.errors[`words.${i}.en`] }}</p>
                        </div>
                        <button
                            type="button"
                            class="admin-btn--remove-sm"
                            :disabled="!canRemove"
                            :aria-label="`Remove word ${i + 1}`"
                            @click="removeWord(i)"
                        >
                            Remove
                        </button>
                    </li>
                </ul>

                <p v-if="form.errors.words" class="admin-form__error">{{ form.errors.words }}</p>

                <button type="button" class="admin-btn--link-sm" @click="addWord">Add a word</button>
            </div>
        </section>

        <div class="admin-form__actions">
            <p v-if="form.recentlySuccessful" class="admin-marquee-form__saved" role="status">Saved.</p>
            <button type="submit" :disabled="form.processing" class="admin-btn--primary">
                {{ form.processing ? 'Saving…' : 'Save running line' }}
            </button>
        </div>
    </form>
</template>
