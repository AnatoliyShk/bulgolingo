<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3'

defineProps({
    mustVerifyEmail: { type: Boolean },
    status: { type: String },
})

const user = usePage().props.auth.user

const form = useForm({
    name: user.name,
    email: user.email,
})
</script>

<template>
    <section class="nb-edit__card">
        <header class="nb-edit__card-head">
            <span class="nb-edit__card-tag nb-edit__card-tag--blue">Профил</span>
            <h2 class="nb-edit__card-title">Profile Information</h2>
            <p class="nb-edit__card-desc">
                Update your account's profile information and email address.
            </p>
        </header>

        <form class="nb-edit__form" @submit.prevent="form.patch(route('profile.update'))">
            <div class="nb-edit__field">
                <label class="nb-edit__label" for="name">Name</label>
                <input
                    id="name"
                    v-model="form.name"
                    class="nb-edit__input"
                    type="text"
                    required
                    autofocus
                    autocomplete="name"
                />
                <p v-if="form.errors.name" class="nb-edit__error">{{ form.errors.name }}</p>
            </div>

            <div class="nb-edit__field">
                <label class="nb-edit__label" for="email">Email</label>
                <input
                    id="email"
                    v-model="form.email"
                    class="nb-edit__input"
                    type="email"
                    required
                    autocomplete="username"
                />
                <p v-if="form.errors.email" class="nb-edit__error">{{ form.errors.email }}</p>
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null" class="nb-edit__note">
                Your email address is unverified.
                <Link
                    :href="route('verification.send')"
                    method="post"
                    as="button"
                    class="nb-edit__note-link"
                >
                    Click here to re-send the verification email.
                </Link>

                <p v-show="status === 'verification-link-sent'" class="nb-edit__note-sent">
                    A new verification link has been sent to your email address.
                </p>
            </div>

            <div class="nb-edit__actions">
                <button type="submit" class="nb-edit__btn" :disabled="form.processing">Save</button>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p v-if="form.recentlySuccessful" class="nb-edit__saved">Saved.</p>
                </Transition>
            </div>
        </form>
    </section>
</template>
