<script setup>
import '@/assets/scss/components/auth.scss'

import { Head, useForm, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import { useTheme } from '@/composables/useTheme'
import PasswordInput from '@/Components/PasswordInput.vue'

const page = usePage()
const appName = computed(() => page.props.appName)
const { theme, toggleTheme } = useTheme()

const form = useForm({
    password: '',
})

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
    })
}
</script>

<template>
    <div class="nb-auth" :class="theme">
        <Head title="Confirm Password">
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
                    <span class="nb-auth__badge"><span lang="bg">Потвърждение</span> · <span class="nb-auth__badge-en">confirm</span></span>
                    <h1 class="nb-auth__title">Confirm password</h1>
                    <p><span class="nb-auth__caption" lang="bg">Сигурна зона</span></p>
                </div>

                <p class="nb-auth__note">
                    This is a secure area of the application. Please confirm your password before continuing.
                </p>

                <form class="nb-auth__form" @submit.prevent="submit">
                    <div class="nb-auth__field">
                        <label for="password" class="nb-auth__label">Password</label>
                        <PasswordInput
                            id="password"
                            v-model="form.password"
                            name="password"
                            input-class="nb-auth__input"
                            required
                            autofocus
                            autocomplete="current-password"
                        />
                        <p v-if="form.errors.password" class="nb-auth__error">{{ form.errors.password }}</p>
                    </div>

                    <button type="submit" class="nb-auth__submit" :disabled="form.processing">
                        Confirm
                        <font-awesome-icon icon="arrow-right" />
                    </button>
                </form>
            </section>
        </main>
    </div>
</template>
