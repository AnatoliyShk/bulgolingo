<script setup>
import '@/assets/scss/components/auth.scss'

import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import { useTheme } from '@/composables/useTheme'
import PasswordInput from '@/Components/PasswordInput.vue'

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
    <div class="nb-auth" :class="theme">
        <Head title="Register">
            <link
                href="https://fonts.bunny.net/css?family=unbounded:400,600,700,800,900|manrope:400,500,600,700,800&subset=cyrillic,latin&display=swap"
                rel="stylesheet"
            />
        </Head>

        <header class="nb-auth__bar">
            <nav class="nb-auth__nav">
                <a href="/" class="nb-auth__logo">
                    <span class="nb-auth__logo-mark" aria-hidden="true">BB</span>
                    <span class="nb-auth__logo-text">{{ appName }}</span>
                </a>
                <div class="nb-auth__links">
                    <a href="/login" class="nb-auth__navlink">Log in</a>
                    <button class="nb-auth__toggle" @click="toggleTheme" :title="theme === 'dark' ? 'Switch to light' : 'Switch to dark'">
                        {{ theme === 'dark' ? '☀' : '☾' }}
                    </button>
                </div>
            </nav>
        </header>

        <main class="nb-auth__main">
            <section class="nb-auth__card">
                <span class="nb-auth__stamp" aria-hidden="true">BB</span>

                <div class="nb-auth__head">
                    <h1 class="nb-auth__title">Start learning</h1>
                    <p><span class="nb-auth__caption" lang="bg">Да започваме!</span></p>
                    <p class="nb-auth__sub">Create an account to <span class="nb-auth__hl">save your progress</span> and keep your streak.</p>
                </div>

                <form class="nb-auth__form" @submit.prevent="submit">
                    <div class="nb-auth__field">
                        <label for="name" class="nb-auth__label">Name</label>
                        <input
                            id="name"
                            v-model="form.name"
                            type="text"
                            name="name"
                            class="nb-auth__input"
                            required
                            autofocus
                            autocomplete="name"
                        />
                        <p v-if="form.errors.name" class="nb-auth__error">{{ form.errors.name }}</p>
                    </div>

                    <div class="nb-auth__field">
                        <label for="email" class="nb-auth__label">Email</label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            name="email"
                            class="nb-auth__input"
                            required
                            autocomplete="username"
                        />
                        <p v-if="form.errors.email" class="nb-auth__error">{{ form.errors.email }}</p>
                    </div>

                    <div class="nb-auth__field">
                        <label for="password" class="nb-auth__label">Password</label>
                        <PasswordInput
                            id="password"
                            v-model="form.password"
                            name="password"
                            input-class="nb-auth__input"
                            required
                            autocomplete="new-password"
                        />
                        <p v-if="form.errors.password" class="nb-auth__error">{{ form.errors.password }}</p>
                    </div>

                    <div class="nb-auth__field">
                        <label for="password_confirmation" class="nb-auth__label">Confirm password</label>
                        <PasswordInput
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            name="password_confirmation"
                            input-class="nb-auth__input"
                            required
                            autocomplete="new-password"
                        />
                        <p v-if="form.errors.password_confirmation" class="nb-auth__error">{{ form.errors.password_confirmation }}</p>
                    </div>

                    <button type="submit" class="nb-auth__submit" :disabled="form.processing">
                        Create account
                        <font-awesome-icon icon="arrow-right" />
                    </button>
                </form>

                <div class="nb-auth__foot">
                    <Link :href="route('login')" class="nb-auth__link nb-auth__link--accent">
                        Already have an account? Log in
                    </Link>
                </div>
            </section>
        </main>
    </div>
</template>
