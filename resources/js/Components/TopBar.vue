<script setup>
import '@/assets/scss/components/top-bar.scss'
import { computed, ref } from 'vue'
import { Link, useForm, usePage } from '@inertiajs/vue3'
import ThemeToggle from '@/Components/ThemeToggle.vue'

const props = defineProps({
    // Keeps the bar's inner column aligned with the content column of the page
    // it sits on — the dashboard is a narrower sheet than the marketing pages.
    maxWidth: {
        type: String,
        default: '1180px',
    },
})

const page = usePage()

const appName = computed(() => page.props.appName)
const isAuthenticated = computed(() => !!page.props.auth?.user)
const isAdmin = computed(() => !!page.props.auth?.isAdmin)

const mobileNavOpen = ref(false)
const closeMobileNav = () => { mobileNavOpen.value = false }

const logoutForm = useForm({})
const logout = () => {
    closeMobileNav()
    logoutForm.post(route('logout'))
}

const links = computed(() => isAuthenticated.value
    ? [
        { label: 'Learning paths', href: '/learning-paths' },
        { label: 'Dashboard', href: '/dashboard' },
        { label: 'Stats', href: '/stats' },
        ...(isAdmin.value ? [{ label: 'Admin', href: '/admin' }] : []),
    ]
    : [
        { label: 'Learning paths', href: '/learning-paths' },
        { label: 'Login', href: '/login' },
        { label: 'Register', href: '/register' },
    ])

// page.url carries the query string; the links are all bare paths.
const currentPath = computed(() => page.url.split('?')[0])
</script>

<template>
    <header class="nb-topbar" :style="{ '--nb-topbar-max': maxWidth }">
        <nav class="nb-topbar__inner">
            <Link href="/" class="nb-topbar__logo" @click="closeMobileNav">
                <span class="nb-topbar__logo-mark" aria-hidden="true">BB</span>
                <span class="nb-topbar__logo-text">{{ appName }}</span>
            </Link>

            <div class="nb-topbar__group">
                <div class="nb-topbar__links" :class="{ 'nb-topbar__links--open': mobileNavOpen }">
                    <Link
                        v-for="link in links"
                        :key="link.href"
                        :href="link.href"
                        class="nb-topbar__link"
                        :aria-current="currentPath === link.href ? 'page' : undefined"
                        @click="closeMobileNav"
                    >{{ link.label }}</Link>

                    <button
                        v-if="isAuthenticated"
                        type="button"
                        class="nb-topbar__link nb-topbar__link--button"
                        @click="logout"
                    >Log out</button>
                </div>

                <div class="nb-topbar__actions">
                    <ThemeToggle />

                    <button
                        class="nb-topbar__hamburger"
                        type="button"
                        :aria-expanded="mobileNavOpen"
                        aria-label="Toggle navigation menu"
                        @click="mobileNavOpen = !mobileNavOpen"
                    >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path v-if="!mobileNavOpen" stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16" />
                            <path v-else stroke-linecap="round" d="M6 6l12 12M18 6L6 18" />
                        </svg>
                    </button>
                </div>
            </div>
        </nav>
    </header>
</template>
