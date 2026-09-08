<script setup>
import Modal from '@/Components/Modal.vue'
import PasswordInput from '@/Components/PasswordInput.vue'
import { useForm } from '@inertiajs/vue3'
import { nextTick, ref } from 'vue'

const confirmingUserDeletion = ref(false)
const passwordInput = ref(null)

const form = useForm({
    password: '',
})

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true

    nextTick(() => passwordInput.value.focus())
}

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    })
}

const closeModal = () => {
    confirmingUserDeletion.value = false

    form.clearErrors()
    form.reset()
}
</script>

<template>
    <section class="nb-edit__card nb-edit__card--danger">
        <header class="nb-edit__card-head">
            <span class="nb-edit__card-tag">Внимание</span>
            <h2 class="nb-edit__card-title">Delete Account</h2>
            <p class="nb-edit__card-desc">
                Once your account is deleted, all of its resources and data will be permanently
                deleted. Before deleting your account, please download any data or information
                that you wish to retain.
            </p>
        </header>

        <div class="nb-edit__actions">
            <button type="button" class="nb-edit__btn nb-edit__btn--danger" @click="confirmUserDeletion">
                Delete Account
            </button>
        </div>

        <Modal :show="confirmingUserDeletion" @close="closeModal">
            <div class="nb-edit__modal">
                <h2 class="nb-edit__modal-title">Are you sure you want to delete your account?</h2>

                <p class="nb-edit__modal-desc">
                    Once your account is deleted, all of its resources and data will be permanently
                    deleted. Please enter your password to confirm you would like to permanently
                    delete your account.
                </p>

                <div class="nb-edit__field">
                    <label class="nb-edit__label" for="password">Password</label>
                    <PasswordInput
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        input-class="nb-edit__input"
                        placeholder="Password"
                        @keyup.enter="deleteUser"
                    />
                    <p v-if="form.errors.password" class="nb-edit__error">{{ form.errors.password }}</p>
                </div>

                <div class="nb-edit__modal-actions">
                    <button type="button" class="nb-edit__btn nb-edit__btn--ghost" @click="closeModal">
                        Cancel
                    </button>
                    <button
                        type="button"
                        class="nb-edit__btn nb-edit__btn--danger"
                        :disabled="form.processing"
                        @click="deleteUser"
                    >
                        Delete Account
                    </button>
                </div>
            </div>
        </Modal>
    </section>
</template>
