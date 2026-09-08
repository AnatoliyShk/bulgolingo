<script setup>
import { useForm, usePage } from '@inertiajs/vue3'
import { computed, onBeforeUnmount, ref } from 'vue'

const props = defineProps({
    avatarUrl: { type: String, default: null },
})

const user = usePage().props.auth.user

const form = useForm({
    avatar: null,
})

const fileInput = ref(null)
const previewUrl = ref(null)

const initial = computed(() => {
    const name = user?.name?.trim() ?? ''
    return name ? Array.from(name)[0].toUpperCase() : '?'
})

// The locally previewed pick wins over the stored one, so the tile shows what
// is about to be saved rather than what is already there.
const shownUrl = computed(() => previewUrl.value ?? props.avatarUrl)

// Object URLs are released as soon as they stop being displayed; the browser
// holds the file alive for as long as one exists.
function releasePreview() {
    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value)
        previewUrl.value = null
    }
}

function pick(event) {
    const file = event.target.files?.[0] ?? null

    releasePreview()
    form.clearErrors()
    form.avatar = file

    if (file) {
        previewUrl.value = URL.createObjectURL(file)
    }
}

function save() {
    form.post(route('profile.avatar.update'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            releasePreview()
            form.reset()
            if (fileInput.value) fileInput.value.value = ''
        },
    })
}

function remove() {
    releasePreview()
    form.avatar = null
    if (fileInput.value) fileInput.value.value = ''

    form.delete(route('profile.avatar.destroy'), { preserveScroll: true })
}

onBeforeUnmount(releasePreview)
</script>

<template>
    <section class="nb-edit__card">
        <header class="nb-edit__card-head">
            <span class="nb-edit__card-tag nb-edit__card-tag--pink">Аватар</span>
            <h2 class="nb-edit__card-title">Profile Picture</h2>
            <p class="nb-edit__card-desc">
                A JPG, PNG or WebP up to 2MB. Without one your profile shows the first letter
                of your name.
            </p>
        </header>

        <div class="nb-edit__avatar-row">
            <div class="nb-edit__avatar" data-testid="avatar-preview">
                <img v-if="shownUrl" :src="shownUrl" class="nb-edit__avatar-img" :alt="`${user.name} avatar`" />
                <span v-else class="nb-edit__avatar-letter">{{ initial }}</span>
            </div>

            <div class="nb-edit__avatar-controls">
                <label class="nb-edit__btn nb-edit__btn--ghost" for="avatar">
                    {{ form.avatar ? 'Change file' : 'Choose file' }}
                </label>
                <input
                    id="avatar"
                    ref="fileInput"
                    class="nb-edit__file"
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    @change="pick"
                />

                <p v-if="form.avatar" class="nb-edit__filename">{{ form.avatar.name }}</p>
                <p v-if="form.errors.avatar" class="nb-edit__error">{{ form.errors.avatar }}</p>
                <p v-if="form.progress" class="nb-edit__saved">Uploading… {{ form.progress.percentage }}%</p>
            </div>
        </div>

        <div class="nb-edit__actions">
            <button
                type="button"
                class="nb-edit__btn"
                :disabled="!form.avatar || form.processing"
                @click="save"
            >
                Save
            </button>

            <button
                v-if="avatarUrl"
                type="button"
                class="nb-edit__btn nb-edit__btn--ghost"
                :disabled="form.processing"
                @click="remove"
            >
                Remove
            </button>
        </div>
    </section>
</template>
