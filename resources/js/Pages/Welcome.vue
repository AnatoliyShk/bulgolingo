<script setup>
import '@/assets/scss/components/welcome.scss'
import { Head, Link, usePage } from '@inertiajs/vue3'
import { computed, ref, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { useTheme } from '@/composables/useTheme'
import TopBar from '@/Components/TopBar.vue'

const page = usePage()
const isAuthenticated = computed(() => !!page.props.auth.user)
const appName = computed(() => page.props.appName)
const continueLessonId = computed(() => page.props.continueLessonId ?? null)
const continueHref = computed(() =>
    continueLessonId.value ? `/lesson/${continueLessonId.value}` : '/learning-paths'
)

const { theme } = useTheme()

const steps = [
    {
        bg: 'Избери своя път',
        en: 'Pick a path',
        body: 'Every learning path is built around something you\'d actually say — ordering food, asking directions, talking to family.',
        tone: 'cyan',
    },
    {
        bg: 'Тренирай всеки ден',
        en: 'Practice daily',
        body: 'Short, mixed exercises — multiple choice, true or false, and more — train your ear as much as your memory.',
        tone: 'pink',
    },
    {
        bg: 'Следи напредъка си',
        en: 'Track your progress',
        body: 'Your dashboard keeps the score: lessons finished, streaks kept, and how far the path still goes.',
        tone: 'blue',
    },
]

// Marquee vocabulary — repeated twice in the template for a seamless loop.
const ticker = [
    { bg: 'Здравей', en: 'hello' },
    { bg: 'Благодаря', en: 'thank you' },
    { bg: 'Вода', en: 'water' },
    { bg: 'Книга', en: 'book' },
    { bg: 'Приятел', en: 'friend' },
    { bg: 'Обичам', en: 'I love' },
    { bg: 'Хляб', en: 'bread' },
    { bg: 'Мляко', en: 'milk' },
]

// Cultural motifs — click a doodle to read about it (English info)
const info = {
    banitsa: {
        icon: '🥧',
        accent: '#e0a043',
        en: 'Banitsa',
        tag: 'Баница',
        lead: 'Bulgaria’s beloved cheese pastry.',
        body: [
            'Thin sheets of filo (kori) are layered with whisked eggs and salty white cheese (sirene), coiled into a spiral, and baked until golden and flaky.',
            'It’s eaten warm — for breakfast, holidays, and family gatherings — often with a glass of ayran or boza.',
            'On New Year’s Eve, small fortunes (късмети) are tucked between the layers; whoever finds one is granted that wish for the year ahead.',
        ],
    },
    rakia: {
        icon: '🍶',
        accent: '#46d39a',
        en: 'Rakia',
        tag: 'Ракия',
        lead: 'The national fruit brandy.',
        body: [
            'A strong spirit — usually 40–60% ABV — distilled from fermented fruit, most often grapes or plums, but also apricots, pears, or quince.',
            'Rakia is often homemade, and families take real pride in their own batch. It’s sipped slowly as an aperitif, classically alongside a shopska salad.',
            'Lift your glass and say “Наздраве!” — “to your health!”',
        ],
    },
}

const activeInfo = ref(null)
const closeBtn = ref(null)
const openInfo = (key) => {
    activeInfo.value = key
    nextTick(() => closeBtn.value?.focus())
}
const closeInfo = () => { activeInfo.value = null }
const onKeydown = (e) => { if (e.key === 'Escape') closeInfo() }

let revealObserver = null
onMounted(() => {
    window.addEventListener('keydown', onKeydown)
    revealObserver = new IntersectionObserver(
        (entries) => {
            entries.forEach(({ target, isIntersecting }) => {
                if (!isIntersecting) return
                target.classList.add('nb-in-view')
                revealObserver.unobserve(target)
            })
        },
        { threshold: 0.12, rootMargin: '0px 0px -48px 0px' }
    )
    document.querySelectorAll('.nb-reveal').forEach(el => revealObserver.observe(el))
})
onBeforeUnmount(() => {
    window.removeEventListener('keydown', onKeydown)
    revealObserver?.disconnect()
})
</script>

<template>
    <Head>
        <link
            href="https://fonts.bunny.net/css?family=unbounded:400,600,700,800,900|manrope:400,500,600,700,800&subset=cyrillic,latin&display=swap"
            rel="stylesheet"
        />
    </Head>

    <div class="nb-page" :class="theme">

        <TopBar />

        <main>

            <!-- ── Hero ── -->
            <section class="nb-hero">
                <div class="nb-hero__text">
                    <h1 class="nb-display" aria-label="Learn Bulgarian">
                        <span class="nb-display__a" lang="bg">УЧИ</span>
                        <span class="nb-display__b" lang="bg">
                            <span class="nb-flag nb-flag--white">БЪЛ</span><span class="nb-flag nb-flag--green">ГАР</span><span class="nb-flag nb-flag--red">СКИ</span>
                        </span>
                    </h1>

                    <div class="nb-actions">
                        <template v-if="isAuthenticated">
                            <Link :href="continueHref" class="nb-btn nb-btn--primary">Continue learning <font-awesome-icon icon="arrow-right" /></Link>
                            <Link href="/dashboard" class="nb-btn nb-btn--ghost">Dashboard</Link>
                        </template>
                        <template v-else>
                            <Link href="/learning-paths" class="nb-btn nb-btn--primary">Start free <font-awesome-icon icon="arrow-right" /></Link>
                            <Link href="/login" class="nb-btn nb-btn--ghost">Log in</Link>
                        </template>
                    </div>
                </div>
            </section>

            <!-- ── Marquee ── -->
            <div class="nb-marquee" aria-hidden="true">
                <div class="nb-marquee__track">
                    <span v-for="(w, i) in [...ticker, ...ticker]" :key="i" class="nb-marquee__item">
                        <span lang="bg">{{ w.bg }}</span>
                        <span class="nb-marquee__en">{{ w.en }}</span>
                        <span class="nb-marquee__star">✸</span>
                    </span>
                </div>
            </div>

            <!-- ── How it works ── -->
            <section id="path" class="nb-path">
                <!-- Banitsa — coiled filo-and-cheese pastry -->
                <button type="button" class="nb-banitsa nb-motif" @click="openInfo('banitsa')" aria-label="About banitsa">
                    <span class="nb-banitsa__pastry"></span>
                    <span class="nb-deco-tag">Баница</span>
                </button>

                <header class="nb-path__head">
                    <span class="nb-badge nb-badge--ink nb-reveal nb-reveal--pop">Твоят път</span>
                    <h2 class="nb-path__title nb-reveal nb-reveal--left" style="--reveal-delay: 90ms">How {{ appName }} works</h2>
                </header>

                <ol class="nb-steps">
                    <li
                        v-for="(step, i) in steps"
                        :key="step.bg"
                        class="nb-card nb-reveal nb-reveal--pop"
                        :class="`nb-card--${step.tone}`"
                        :style="{ '--reveal-delay': `${i * 130}ms` }"
                    >
                        <span class="nb-card__num">{{ String(i + 1).padStart(2, '0') }}</span>
                        <h3 class="nb-card__title" lang="bg">{{ step.bg }}</h3>
                        <span class="nb-card__en">{{ step.en }}</span>
                        <p class="nb-card__text">{{ step.body }}</p>
                    </li>
                </ol>

                <div class="nb-cta-band nb-reveal" style="--reveal-delay: 60ms">
                    <!-- Rakia — fruit brandy in a bottle, наздраве! -->
                    <button type="button" class="nb-rakia nb-motif" @click="openInfo('rakia')" aria-label="About rakia">
                        <span class="nb-bottle">
                            <span class="nb-bottle__cap"></span>
                            <span class="nb-bottle__neck"></span>
                            <span class="nb-bottle__body"><span class="nb-bottle__label"></span></span>
                        </span>
                        <span class="nb-deco-tag">Ракия</span>
                    </button>
                    <p class="nb-cta-band__text" lang="bg">Готов ли си? <span>Ready?</span></p>
                    <Link
                        :href="isAuthenticated ? continueHref : '/register'"
                        class="nb-btn nb-btn--primary nb-btn--lg"
                    >
                        {{ isAuthenticated ? 'Keep going' : 'Start learning' }} <font-awesome-icon icon="arrow-right" />
                    </Link>
                </div>
            </section>

        </main>

        <!-- ── Footer ── -->
        <footer class="nb-footer">
            <span class="nb-logo__mark nb-reveal" aria-hidden="true">BB</span>
            <p class="nb-footer__text nb-reveal" style="--reveal-delay: 120ms">
                <strong>{{ appName }}</strong>
                <span lang="bg"> — учи български, дума по дума.</span>
                <br>Learn Bulgarian, one word at a time.
            </p>
        </footer>

        <!-- ── Info modal (brutalist) ── -->
        <Transition name="nb-modal">
            <div v-if="activeInfo" class="nb-modal" @click.self="closeInfo">
                <div class="nb-modal__box" role="dialog" aria-modal="true" :aria-label="info[activeInfo].en">
                    <header class="nb-modal__head">
                        <span class="nb-modal__icon" :style="{ background: info[activeInfo].accent }">{{ info[activeInfo].icon }}</span>
                        <div class="nb-modal__heading">
                            <h2 class="nb-modal__title">{{ info[activeInfo].en }}</h2>
                            <span class="nb-modal__bg" lang="bg">{{ info[activeInfo].tag }}</span>
                        </div>
                        <button ref="closeBtn" type="button" class="nb-modal__close" @click="closeInfo" aria-label="Close">✕</button>
                    </header>
                    <p class="nb-modal__lead">{{ info[activeInfo].lead }}</p>
                    <p v-for="(para, i) in info[activeInfo].body" :key="i" class="nb-modal__p">{{ para }}</p>
                </div>
            </div>
        </Transition>

    </div>
</template>

