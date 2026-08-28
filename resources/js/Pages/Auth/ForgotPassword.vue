<script setup>
import '@/assets/scss/components/auth.scss'

import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import { useTheme } from '@/composables/useTheme'
import ThemeToggle from '@/Components/ThemeToggle.vue'

defineProps({
    status: {
        type: String,
    },
})

const page = usePage()
const appName = computed(() => page.props.appName)
const { theme } = useTheme()

const form = useForm({
    email: '',
})

const submit = () => {
    form.post(route('password.email'))
}
</script>

<template>
    <div class="nb-auth" :class="theme">
        <Head title="Forgot Password">
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
                    <ThemeToggle />
                </div>
            </nav>
        </header>

        <main class="nb-auth__main">
            <section class="nb-auth__card">
                <span class="nb-auth__stamp" aria-hidden="true">BB</span>

                <div class="nb-auth__head">
                    <span class="nb-auth__badge"><span lang="bg">Възстановяване</span> · <span class="nb-auth__badge-en">reset</span></span>
                    <h1 class="nb-auth__title">Forgot password?</h1>
                    <p><span class="nb-auth__caption" lang="bg">Няма проблем</span></p>
                    <p class="nb-auth__sub">Tell us your email and we'll send you a <span class="nb-auth__hl">reset link.</span></p>
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

                    <button type="submit" class="nb-auth__submit" :disabled="form.processing">
                        Email reset link
                        <font-awesome-icon icon="arrow-right" />
                    </button>
                </form>

                <div class="nb-auth__foot">
                    <Link :href="route('login')" class="nb-auth__link nb-auth__link--accent">
                        Back to log in
                    </Link>
                </div>
            </section>
        </main>
    </div>
</template>
