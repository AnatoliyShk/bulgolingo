<script setup>
import '@/assets/scss/components/profile/edit.scss'
import { Link } from '@inertiajs/vue3'
import { useTheme } from '@/composables/useTheme'
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import DeleteUserForm from './Partials/DeleteUserForm.vue'
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue'
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue'

defineProps({
    mustVerifyEmail: { type: Boolean },
    status: { type: String },
})

const { theme, toggleTheme } = useTheme()
const page = usePage()
const appName = computed(() => page.props.appName ?? 'Bulgolingo')
</script>

<template>
    <div class="pg" :class="theme">
        <div class="pg__watermark" aria-hidden="true">Ъ</div>

        <header class="bar">
            <Link :href="route('dashboard')" class="bar__back" aria-label="Back to dashboard">
                <svg class="bar__back-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </Link>
            <span class="bar__mark">{{ appName }}</span>
            <button class="bar__theme" @click="toggleTheme" :title="theme === 'dark' ? 'Switch to light' : 'Switch to dark'">
                {{ theme === 'dark' ? '☀️' : '🌙' }}
            </button>
        </header>

        <main class="sheet">
            <p class="eyebrow">
                <span class="eyebrow__bg">Профил</span>
                <span class="eyebrow__en">edit profile</span>
            </p>

            <div class="cards">
                <div class="card">
                    <UpdateProfileInformationForm :must-verify-email="mustVerifyEmail" :status="status" />
                </div>

                <div class="card">
                    <UpdatePasswordForm />
                </div>

                <div class="card card--danger">
                    <DeleteUserForm />
                </div>
            </div>
        </main>
    </div>
</template>

