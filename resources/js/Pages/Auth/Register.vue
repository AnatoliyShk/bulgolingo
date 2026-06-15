<script setup>
import '../../../css/home.css'
import '../../../css/home-light.css'
import '../../../css/home-dark.css'
import '../../../css/auth.css'

import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import { useTheme } from '@/composables/useTheme'

const page = usePage()
const appName = computed(() => page.props.appName)
const { theme, toggleTheme } = useTheme()

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
})

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    })
}
</script>

<template>
    <div class="page" :class="theme">
        <Head title="Register" />

        <header class="header">
            <nav class="nav">
                <a href="/" class="nav__logo">
                    <span class="nav__mark" aria-hidden="true">Ъ</span>
                    {{ appName }}
                </a>
                <div class="nav__links">
                    <a href="/login" class="nav__link">Log in</a>
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
                        <span lang="bg">Регистрация</span>
                        <span class="auth-card__eyebrow-en">sign up</span>
                    </p>
                    <h1 class="auth-card__title">Start your journey</h1>
                    <p class="auth-card__caption" lang="bg">«Да започваме»</p>
                    <p class="auth-card__subtitle">Create an account to save your progress and keep your streak.</p>
                    <div class="thread-divider" aria-hidden="true"></div>
                </div>

                <form class="auth-form" @submit.prevent="submit">
                    <div class="field">
                        <label for="name" class="field__label">Name</label>
                        <input
                            id="name"
                            v-model="form.name"
                            type="text"
                            class="field__input"
                            required
                            autofocus
                            autocomplete="name"
                        />
                        <p v-if="form.errors.name" class="field__error">{{ form.errors.name }}</p>
                    </div>

                    <div class="field">
                        <label for="email" class="field__label">Email</label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            class="field__input"
                            required
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
                            autocomplete="new-password"
                        />
                        <p v-if="form.errors.password" class="field__error">{{ form.errors.password }}</p>
                    </div>

                    <div class="field">
                        <label for="password_confirmation" class="field__label">Confirm password</label>
                        <input
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            type="password"
                            class="field__input"
                            required
                            autocomplete="new-password"
                        />
                        <p v-if="form.errors.password_confirmation" class="field__error">{{ form.errors.password_confirmation }}</p>
                    </div>

                    <button type="submit" class="btn btn--primary auth-form__submit" :disabled="form.processing">
                        Create account
                    </button>
                </form>

                <div class="auth-card__footer">
                    <Link :href="route('login')" class="auth-card__link auth-card__link--accent">
                        Already have an account? Log in
                    </Link>
                </div>
            </section>
        </main>
    </div>
</template>
