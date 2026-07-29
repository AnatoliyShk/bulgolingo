<script setup>
import '@/assets/scss/components/auth.scss'

import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import { useTheme } from '@/composables/useTheme'
import PasswordInput from '@/Components/PasswordInput.vue'

const props = defineProps({
    email: {
        type: String,
        required: true,
    },
    token: {
        type: String,
        required: true,
    },
})

const page = usePage()
const appName = computed(() => page.props.appName)
const { theme, toggleTheme } = useTheme()

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
})

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    })
}
</script>

<template>
    <div class="nb-auth" :class="theme">
        <Head title="Reset Password">
            <link
                href="https://fonts.bunny.net/css?family=unbounded:400,600,700,800,900|manrope:400,500,600,700,800&subset=cyrillic,latin&display=swap"
                rel="stylesheet"
            />
        </Head>

        <header class="nb-auth__bar">
            <nav class="nb-auth__nav">
                <Link href="/" class="nb-auth__logo">
                    <span class="nb-auth__logo-mark" aria-hidden="true">BB</span>
                    <span class="nb-auth__logo-text">{{ appName }}</span>
                </Link>
                <div class="nb-auth__links">
                    <Link href="/login" class="nb-auth__navlink">Log in</Link>
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
                    <span class="nb-auth__badge"><span lang="bg">Нова парола</span> · <span class="nb-auth__badge-en">new password</span></span>
                    <h1 class="nb-auth__title">Reset password</h1>
                    <p><span class="nb-auth__caption" lang="bg">Готови сме</span></p>
                    <p class="nb-auth__sub">Choose a new password to <span class="nb-auth__hl">get back in.</span></p>
                </div>

                <form class="nb-auth__form" @submit.prevent="submit">
                    <div class="nb-auth__field">
                        <label for="email" class="nb-auth__label">Email</label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            name="email"
                            class="nb-auth__input"
                            required
                            autofocus
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
                        Reset password
                        <font-awesome-icon icon="arrow-right" />
                    </button>
                </form>
            </section>
        </main>
    </div>
</template>
