<script setup>
import '@/assets/scss/components/auth.scss'

import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import { useTheme } from '@/composables/useTheme'
import PasswordInput from '@/Components/PasswordInput.vue'
import ThemeToggle from '@/Components/ThemeToggle.vue'

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
const { theme } = useTheme()

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
    <div class="nb-auth" :class="theme">
        <Head title="Log in">
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
                    <Link href="/register" class="nb-auth__navlink nb-auth__navlink--cta">Register</Link>
                    <ThemeToggle />
                </div>
            </nav>
        </header>

        <main class="nb-auth__main">
            <section class="nb-auth__card">
                <span class="nb-auth__stamp" aria-hidden="true">BB</span>

                <div class="nb-auth__head">
                    <h1 class="nb-auth__title">Welcome back</h1>
                    <p><span class="nb-auth__caption" lang="bg">Добре дошли отново</span></p>
                    <p class="nb-auth__sub">Pick up right where you left off — <span class="nb-auth__hl">your streak is waiting.</span></p>
                </div>

                <p v-if="status" class="nb-auth__status">{{ status }}</p>

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
                            autocomplete="current-password"
                        />
                        <p v-if="form.errors.password" class="nb-auth__error">{{ form.errors.password }}</p>
                    </div>

                    <label class="nb-auth__check">
                        <input v-model="form.remember" type="checkbox" name="remember" class="nb-auth__checkbox" />
                        Remember me
                    </label>

                    <button type="submit" class="nb-auth__submit" :disabled="form.processing">
                        Log in
                        <font-awesome-icon icon="arrow-right" />
                    </button>
                </form>

                <div class="nb-auth__foot nb-auth__foot--split">
                    <Link v-if="canResetPassword" :href="route('password.request')" class="nb-auth__link">
                        Forgot password?
                    </Link>
                    <Link :href="route('register')" class="nb-auth__link nb-auth__link--accent">
                        Create account
                    </Link>
                </div>
            </section>
        </main>
    </div>
</template>
