<script setup>
import '@/assets/scss/components/auth.scss'

import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import { useTheme } from '@/composables/useTheme'

const props = defineProps({
    status: {
        type: String,
    },
})

const page = usePage()
const appName = computed(() => page.props.appName)
const { theme, toggleTheme } = useTheme()

const form = useForm({})

const submit = () => {
    form.post(route('verification.send'))
}

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
)
</script>

<template>
    <div class="nb-auth" :class="theme">
        <Head title="Email Verification">
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
                    <span class="nb-auth__badge"><span lang="bg">Потвърждение</span> · <span class="nb-auth__badge-en">verify</span></span>
                    <h1 class="nb-auth__title">Verify your email</h1>
                    <p><span class="nb-auth__caption" lang="bg">Почти готово</span></p>
                </div>

                <p class="nb-auth__note">
                    Thanks for signing up! Before getting started, please verify your email address by clicking the
                    link we just emailed to you. Didn't receive it? We'll gladly send another.
                </p>

                <p v-if="verificationLinkSent" class="nb-auth__status">
                    A new verification link has been sent to the email address you provided during registration.
                </p>

                <form class="nb-auth__form" @submit.prevent="submit">
                    <button type="submit" class="nb-auth__submit" :disabled="form.processing">
                        Resend verification email
                        <font-awesome-icon icon="arrow-right" />
                    </button>
                </form>

                <div class="nb-auth__foot">
                    <Link :href="route('logout')" method="post" as="button" class="nb-auth__link">
                        Log out
                    </Link>
                </div>
            </section>
        </main>
    </div>
</template>
