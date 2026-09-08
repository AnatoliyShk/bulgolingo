<script setup>
import '@/assets/scss/components/profile/edit.scss'
import { Head } from '@inertiajs/vue3'
import { useTheme } from '@/composables/useTheme'
import TopBar from '@/Components/TopBar.vue'
import DeleteUserForm from './Partials/DeleteUserForm.vue'
import UpdateAvatarForm from './Partials/UpdateAvatarForm.vue'
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue'
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue'

defineProps({
    mustVerifyEmail: { type: Boolean },
    status: { type: String },
    avatarUrl: { type: String, default: null },
})

const { theme } = useTheme()
</script>

<template>
    <Head>
        <link
            href="https://fonts.bunny.net/css?family=unbounded:400,600,700,800,900|manrope:400,500,600,700,800&subset=cyrillic,latin&display=swap"
            rel="stylesheet"
        />
    </Head>

    <div class="nb-edit" :class="theme">

        <TopBar />

        <main class="nb-edit__main">
            <header class="nb-edit__head">
                <h1 class="nb-edit__title">Настройки</h1>
                <span class="nb-edit__badge">settings</span>
            </header>

            <div class="nb-edit__cards">
                <UpdateAvatarForm :avatar-url="avatarUrl" />
                <UpdateProfileInformationForm :must-verify-email="mustVerifyEmail" :status="status" />
                <UpdatePasswordForm />
                <DeleteUserForm />
            </div>
        </main>
    </div>
</template>
