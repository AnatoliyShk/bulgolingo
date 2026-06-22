<script setup>
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

<style scoped>
.pg {
    position: relative;
    min-height: 100vh;
    overflow-x: hidden;
    font-family: 'PT Sans', sans-serif;
    background: var(--bg);
    color: var(--ink);
    transition: background .3s ease, color .3s ease;
}

.pg.light {
    --bg: #fbf6ec;
    --surface: #ffffff;
    --ink: #2b231b;
    --muted: #8a7a66;
    --rose: #b3273e;
    --gold: #b9862e;
    --border: rgba(43, 35, 27, .12);
    --danger-border: rgba(179, 39, 62, .25);
}

.pg.dark {
    --bg: #1b1712;
    --surface: #27201a;
    --ink: #f3e9d8;
    --muted: #a4937c;
    --rose: #e2697b;
    --gold: #e0b45a;
    --border: rgba(243, 233, 216, .1);
    --danger-border: rgba(226, 105, 123, .25);
}

.pg__watermark {
    position: fixed;
    top: 50%;
    right: -8vw;
    transform: translateY(-50%);
    font-family: 'PT Serif', serif;
    font-weight: 700;
    font-size: min(70vw, 60rem);
    line-height: 1;
    color: var(--ink);
    opacity: .03;
    pointer-events: none;
    user-select: none;
    z-index: 0;
}

.bar {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    justify-content: space-between;
    max-width: 42rem;
    margin: 0 auto;
    padding: 1.5rem 1.5rem 0;
}

.bar__back,
.bar__theme {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 50%;
    color: var(--muted);
    transition: color .2s ease, border-color .2s ease;
}
.bar__back:hover { color: var(--rose); }
.bar__back-icon  { width: 1.1rem; height: 1.1rem; }

.bar__mark {
    font-family: 'PT Serif', serif;
    font-weight: 700;
    font-size: .8rem;
    letter-spacing: .22em;
    text-transform: uppercase;
    color: var(--muted);
}

.bar__theme {
    border: 1px solid var(--border);
    background: var(--surface);
    font-size: 1rem;
    line-height: 1;
    cursor: pointer;
}
.bar__theme:hover { color: var(--rose); border-color: var(--rose); }

.sheet {
    position: relative;
    z-index: 1;
    max-width: 42rem;
    margin: 0 auto;
    padding: 2.75rem 1.5rem 4rem;
}

.eyebrow {
    display: flex;
    align-items: baseline;
    gap: .5em;
    margin: 0 0 1.75rem;
}
.eyebrow__bg {
    font-family: 'PT Serif', serif;
    font-weight: 700;
    font-size: .8rem;
    letter-spacing: .14em;
    text-transform: uppercase;
    color: var(--rose);
}
.eyebrow__en {
    font-size: .72rem;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: var(--muted);
}
.eyebrow__en::before { content: '· '; }

.cards {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: .85rem;
    padding: 1.75rem;
}

.card--danger {
    border-color: var(--danger-border);
}

.bar__back:focus-visible,
.bar__theme:focus-visible {
    outline: 2px solid var(--rose);
    outline-offset: 2px;
}
</style>
