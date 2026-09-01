<!-- resources/js/Pages/Profile/Show.vue -->
<script setup>
import '@/assets/scss/components/profile/show.scss'
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { useTheme } from '@/composables/useTheme'
import TopBar from '@/Components/TopBar.vue'
import LearningPathCard from '@/Components/LearningPathCard.vue'

const props = defineProps({
    user: Object,
    activeLearningPath: {
        type: Object,
        default: null,
    },
    enrolledCount: {
        type: Number,
        default: 0,
    },
    finishedCount: {
        type: Number,
        default: 0,
    },
    appName: {
        type: String,
        default: 'BalkanBuddy',
    },
})

const { theme } = useTheme()

const initial = computed(() => {
    const name = props.user?.name?.trim() ?? ''
    return name ? Array.from(name)[0].toUpperCase() : '?'
})

const isVerified = computed(() => Boolean(props.user?.email_verified_at))

const verificationLabel = computed(() => (isVerified.value ? 'Email verified' : 'Email not verified'))

const experience = computed(() => props.user?.experience ?? 0)

const memberSince = computed(() => {
    if (!props.user?.created_at) return null
    return new Intl.DateTimeFormat('en', { month: 'long', year: 'numeric' }).format(new Date(props.user.created_at))
})

const isPremium = computed(() => props.user?.type === 'premium')
</script>

<template>
    <Head>
        <link
            href="https://fonts.bunny.net/css?family=unbounded:400,600,700,800,900|manrope:400,500,600,700,800&subset=cyrillic,latin&display=swap"
            rel="stylesheet"
        />
    </Head>

    <div class="nb-prof" :class="theme">

        <TopBar />

        <main class="nb-prof__main">

            <!-- Identity -->
            <section class="nb-prof__hero">
                <div class="nb-prof__avatar-col">
                    <div class="nb-prof__avatar">
                        <span class="nb-prof__avatar-letter">{{ initial }}</span>
                        <span
                            class="nb-prof__avatar-badge"
                            :class="isVerified ? 'nb-prof__avatar-badge--verified' : 'nb-prof__avatar-badge--unverified'"
                            role="img"
                            :aria-label="verificationLabel"
                            :title="verificationLabel"
                        >
                            <svg v-if="isVerified" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4.5 4.5L19 7" />
                            </svg>
                            <svg v-else fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3.5">
                                <path stroke-linecap="round" d="M12 6v8" />
                                <path stroke-linecap="round" d="M12 18.2v.1" />
                            </svg>
                        </span>
                    </div>
                    <div class="nb-prof__xp">XP: {{ experience }}</div>
                </div>
                <div class="nb-prof__info nb-prof__info--card">
                    <div class="nb-prof__eyebrow">
                        Профил <span class="nb-prof__eyebrow-en">· profile</span>
                    </div>
                    <h1 class="nb-prof__name">
                        {{ user.name }}
                        <span v-if="isPremium" class="nb-prof__premium-star" aria-label="Premium member">★ Premium</span>
                    </h1>
                    <p class="nb-prof__email">{{ user.email }}</p>
                    <p v-if="memberSince" class="nb-prof__member">Learning Bulgarian since {{ memberSince }}</p>
                </div>
                <Link :href="route('profile.edit')" class="nb-prof__edit-btn" aria-label="Edit profile">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </Link>
            </section>

            <!-- Learning paths -->
            <section class="nb-prof__section">
                <div class="nb-prof__section-head">
                    <h2 class="nb-prof__section-title">Пътят</h2>
                    <span class="nb-prof__section-badge">your path</span>
                </div>

                <template v-if="activeLearningPath">
                    <LearningPathCard :path="activeLearningPath" />

                    <div class="nb-prof__path-links">
                        <Link :href="route('learning-paths.enrolled')" class="nb-prof__path-link">
                            All enrolled
                            <span v-if="enrolledCount" class="nb-prof__path-link-count">{{ enrolledCount }}</span>
                        </Link>
                        <Link :href="route('learning-paths.finished')" class="nb-prof__path-link">
                            All finished
                            <span v-if="finishedCount" class="nb-prof__path-link-count">{{ finishedCount }}</span>
                        </Link>
                    </div>
                </template>

                <div v-else class="nb-prof__empty">
                    <p class="nb-prof__empty-title">{{ finishedCount > 0 ? 'Поздравления!' : 'Няма избран път' }}</p>
                    <p class="nb-prof__empty-sub">{{ finishedCount > 0 ? 'You have finished all your paths. Browse new ones!' : "You haven't picked a learning path yet." }}</p>
                    <div class="nb-prof__empty-actions">
                        <Link :href="route('learning-paths.index')" class="nb-prof__empty-btn">{{ finishedCount > 0 ? 'Browse paths' : 'Choose a path' }} <font-awesome-icon icon="arrow-right-long" /></Link>
                        <Link v-if="finishedCount > 0" :href="route('learning-paths.finished')" class="nb-prof__empty-btn nb-prof__empty-btn--secondary">All finished <font-awesome-icon icon="arrow-right-long" /></Link>
                    </div>
                </div>
            </section>

            <!-- Stats link -->
            <Link :href="route('stats.show')" class="nb-prof__stats">
                <div class="nb-prof__stats-label">
                    <span class="nb-prof__stats-bg">Статистика</span>
                    <span class="nb-prof__stats-en">view your stats</span>
                </div>
                <span class="nb-prof__stats-arrow" aria-hidden="true"><font-awesome-icon icon="eye" /></span>
            </Link>

        </main>
    </div>
</template>
