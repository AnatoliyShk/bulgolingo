<script setup>
import PasswordInput from '@/Components/PasswordInput.vue'
import { useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

const passwordInput = ref(null)
const currentPasswordInput = ref(null)

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
})

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation')
                passwordInput.value.focus()
            }
            if (form.errors.current_password) {
                form.reset('current_password')
                currentPasswordInput.value.focus()
            }
        },
    })
}
</script>

<template>
    <section class="nb-edit__card">
        <header class="nb-edit__card-head">
            <span class="nb-edit__card-tag nb-edit__card-tag--purple">Парола</span>
            <h2 class="nb-edit__card-title">Update Password</h2>
            <p class="nb-edit__card-desc">
                Ensure your account is using a long, random password to stay secure.
            </p>
        </header>

        <form class="nb-edit__form" @submit.prevent="updatePassword">
            <div class="nb-edit__field">
                <label class="nb-edit__label" for="current_password">Current Password</label>
                <PasswordInput
                    id="current_password"
                    ref="currentPasswordInput"
                    v-model="form.current_password"
                    input-class="nb-edit__input"
                    autocomplete="current-password"
                />
                <p v-if="form.errors.current_password" class="nb-edit__error">
                    {{ form.errors.current_password }}
                </p>
            </div>

            <div class="nb-edit__field">
                <label class="nb-edit__label" for="password">New Password</label>
                <PasswordInput
                    id="password"
                    ref="passwordInput"
                    v-model="form.password"
                    input-class="nb-edit__input"
                    autocomplete="new-password"
                />
                <p v-if="form.errors.password" class="nb-edit__error">{{ form.errors.password }}</p>
            </div>

            <div class="nb-edit__field">
                <label class="nb-edit__label" for="password_confirmation">Confirm Password</label>
                <PasswordInput
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    input-class="nb-edit__input"
                    autocomplete="new-password"
                />
                <p v-if="form.errors.password_confirmation" class="nb-edit__error">
                    {{ form.errors.password_confirmation }}
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
