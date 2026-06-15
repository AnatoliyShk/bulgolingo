<script setup>
import '../../../css/home.css'
import '../../../css/home-light.css'
import '../../../css/home-dark.css'
import '../../../css/auth.css'

import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import { useTheme } from '@/composables/useTheme'

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
})

const page = usePage()
const appName = computed(() => page.props.appName)
const { theme, toggleTheme } = useTheme()

const form = useForm({
    email: '',
    password: '',
    remember: false,
})

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    })
}
</script>

<template>
    <div class="page" :class="theme">
        <Head title="Log in" />

        <header class="header">
            <nav class="nav">
                <a href="/" class="nav__logo">
                    <span class="nav__mark" aria-hidden="true">Ъ</span>
                    {{ appName }}
                </a>
                <div class="nav__links">
                    <a href="/register" class="nav__link nav__link--cta">Create account</a>
                    <button class="theme-toggle" @click="toggleTheme" :title="theme === 'dark' ? 'Switch to light' : 'Switch to dark'">
                        {{ theme === 'dark' ? '☀️' : '🌙' }}
                    </button>
                </div>
            </nav>
        </header>

        <main class="auth">
            <span class="page__mark" aria-hidden="true">Ъ</span>

            <section class="auth-card">
                <div class="auth-card__head">
                    <p class="auth-card__eyebrow">
                        <span lang="bg">Вход</span>
                        <span class="auth-card__eyebrow-en">log in</span>
                    </p>
                    <h1 class="auth-card__title">Welcome back</h1>
                    <p class="auth-card__caption" lang="bg">«Добре дошли отново»</p>
                    <p class="auth-card__subtitle">Pick up right where you left off — your streak is waiting.</p>
                    <div class="thread-divider" aria-hidden="true"></div>
                </div>

                <p v-if="status" class="auth-card__status">{{ status }}</p>

                <form class="auth-form" @submit.prevent="submit">
                    <div class="field">
                        <label for="email" class="field__label">Email</label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            class="field__input"
                            required
                            autofocus
                            autocomplete="username"
                        />
                        <p v-if="form.errors.email" class="field__error">{{ form.errors.email }}</p>
                    </div>

                    <div class="field">
                        <label for="password" class="field__label">Password</label>
                        <input
                            id="password"
                            v-model="form.password"
                            type="password"
                            class="field__input"
                            required
                            autocomplete="current-password"
                        />
                        <p v-if="form.errors.password" class="field__error">{{ form.errors.password }}</p>
                    </div>

                    <label class="field--checkbox">
                        <input v-model="form.remember" type="checkbox" name="remember" class="field__checkbox" />
                        Remember me
                    </label>

                    <button type="submit" class="btn btn--primary auth-form__submit" :disabled="form.processing">
                        Log in
                    </button>
                </form>

                <div class="auth-card__footer auth-card__footer--split">
                    <Link v-if="canResetPassword" :href="route('password.request')" class="auth-card__link">
                        Forgot your password?
                    </Link>
                    <Link :href="route('register')" class="auth-card__link auth-card__link--accent">
                        Create an account
                    </Link>
                </div>
            </section>
        </main>
    </div>
</template>
