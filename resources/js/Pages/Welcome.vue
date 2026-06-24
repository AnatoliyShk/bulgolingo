<script setup>
import { Head, usePage } from '@inertiajs/vue3'
import { computed, ref, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { useTheme } from '@/composables/useTheme'

const page = usePage()
const isAuthenticated = computed(() => !!page.props.auth.user)
const appName = computed(() => page.props.appName)
const isAdmin = computed(() => page.props.auth.isAdmin)
const continueLessonId = computed(() => page.props.continueLessonId ?? null)
const continueHref = computed(() =>
    continueLessonId.value ? `/lesson/${continueLessonId.value}` : '/learning-paths'
)

const { theme, toggleTheme } = useTheme()

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
    martenitsa: {
        icon: '🧶',
        accent: '#ff6ba3',
        en: 'Martenitsa',
        tag: 'Мартеница',
        lead: 'A red-and-white token of health and spring.',
        body: [
            'Bulgarians exchange martenitsi every March 1st for Baba Marta — “Grandma March” — wishing one another health, luck, and happiness.',
            'You pin it to your clothes or tie it to your wrist and wear it until you see the first sign of spring: a stork, a swallow, or a blossoming tree. Then you tie it to a branch.',
            'The classic pair of yarn dolls is Pizho (white) and Penda (red) — white for purity and long life, red for vitality and the blood of life.',
        ],
    },
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

        <!-- ── Nav ── -->
        <header class="nb-nav">
            <nav class="nb-nav__inner">
                <a href="/" class="nb-logo">
                    <span class="nb-logo__mark" aria-hidden="true">Ъ</span>
                    <span class="nb-logo__text">{{ appName }}</span>
                </a>

                <div class="nb-nav__links">
                    <a :href="isAuthenticated ? continueHref : '#path'" class="nb-navlink">Your path</a>
                    <template v-if="isAuthenticated">
                        <a href="/dashboard" class="nb-navlink">Profile</a>
                        <a v-if="isAdmin" href="/admin" class="nb-navlink">Admin</a>
                    </template>
                    <template v-else>
                        <a href="/login" class="nb-navlink">Login</a>
                        <a href="/register" class="nb-navlink nb-navlink--cta">Register</a>
                    </template>
                    <button
                        class="nb-toggle"
                        @click="toggleTheme"
                        :title="theme === 'dark' ? 'Switch to light' : 'Switch to dark'"
                    >
                        {{ theme === 'dark' ? '☀' : '☾' }}
                    </button>
                </div>
            </nav>
        </header>

        <main>

            <!-- ── Hero ── -->
            <section class="nb-hero">
                <div class="nb-hero__text">
                    <span class="nb-badge nb-badge--cyan">★ Учи български · Learn Bulgarian</span>

                    <h1 class="nb-display" aria-label="Learn Bulgarian">
                        <span class="nb-display__a" lang="bg">УЧИ</span>
                        <span class="nb-display__b" lang="bg">
                            <span class="nb-flag nb-flag--white">БЪЛ</span><span class="nb-flag nb-flag--green">ГАР</span><span class="nb-flag nb-flag--red">СКИ</span>
                        </span>
                    </h1>

                    <p class="nb-lede">
                        <strong lang="bg">Здравей</strong>
                        <span class="nb-phon">zdra-VEY</span>
                        — that's hello, and your first word. Bulgarian has its own
                        alphabet, its own sounds, and its own logic.
                        <span class="nb-hl">{{ appName }} teaches all three.</span>
                    </p>

                    <div class="nb-actions">
                        <template v-if="isAuthenticated">
                            <a :href="continueHref" class="nb-btn nb-btn--primary">Continue learning →</a>
                            <a href="/dashboard" class="nb-btn nb-btn--ghost">Dashboard</a>
                        </template>
                        <template v-else>
                            <a href="/register" class="nb-btn nb-btn--primary">Start free →</a>
                            <a href="/login" class="nb-btn nb-btn--ghost">Log in</a>
                        </template>
                    </div>
                </div>

                <!-- Visual cluster -->
                <div class="nb-hero__art">
                    <!-- Martenitsa — Пижо (white) & Пенда (red), worn for Баба Марта -->
                    <button type="button" class="nb-marten nb-motif" @click="openInfo('martenitsa')" aria-label="About the martenitsa">
                        <span class="nb-marten__cord"></span>
                        <div class="nb-marten__dolls">
                            <span class="nb-marten__doll nb-marten__doll--white">
                                <span class="nb-marten__head"></span>
                                <span class="nb-marten__body"></span>
                                <span class="nb-marten__legs"></span>
                            </span>
                            <span class="nb-marten__doll nb-marten__doll--red">
                                <span class="nb-marten__head"></span>
                                <span class="nb-marten__body"></span>
                                <span class="nb-marten__legs"></span>
                            </span>
                        </div>
                        <span class="nb-marten__tag">Баба Марта</span>
                    </button>

                    <div class="nb-tile" aria-hidden="true">
                        <span class="nb-tile__glyph">Ъ</span>
                        <span class="nb-tile__name">ER GOLYAM</span>
                    </div>
                    <div class="nb-chip nb-chip--pink" aria-hidden="true">Аз → I</div>
                    <div class="nb-chip nb-chip--cyan" aria-hidden="true">Ти → you</div>
                    <div class="nb-chip nb-chip--green" aria-hidden="true">Ние → we</div>
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
                    <a
                        :href="isAuthenticated ? continueHref : '/register'"
                        class="nb-btn nb-btn--primary nb-btn--lg"
                    >
                        {{ isAuthenticated ? 'Keep going →' : 'Start learning →' }}
                    </a>
                </div>
            </section>

        </main>

        <!-- ── Footer ── -->
        <footer class="nb-footer">
            <span class="nb-logo__mark nb-reveal" aria-hidden="true">Ъ</span>
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

<style scoped>
/* ══════════════ Neo-Brutalist tokens ══════════════ */
.nb-page {
    --ink:    #14110b;
    --bg:     #f1e9d6;
    --paper:  #fffaf0;
    --on-accent: #14110b;  /* text on colored fills — stays dark in both themes */

    --cyan:   #34d3da;
    --pink:   #ff6ba3;
    --blue:   #5b8def;
    --green:  #46d39a;
    --orange: #ff8a47;
    --purple: #b388ff;

    /* Bulgarian flag — fixed in both themes */
    --flag-green: #00966e;
    --flag-red:   #d62612;

    --border: 3px solid var(--ink);
    --shadow:     6px 6px 0 0 var(--ink);
    --shadow-sm:  4px 4px 0 0 var(--ink);
    --radius: 12px;

    font-family: 'Manrope', system-ui, sans-serif;
    background: var(--bg);
    color: var(--ink);
    min-height: 100vh;
    overflow-x: hidden;
    /* subtle brutalist dot grid */
    background-image: radial-gradient(var(--ink) 1.2px, transparent 1.2px);
    background-size: 22px 22px;
    background-position: -11px -11px;
}
.nb-page * { box-sizing: border-box; }

.nb-page.dark {
    --ink:   #f4ead6;
    --bg:    #17140f;
    --paper: #221d16;
}
.nb-page.dark { background-image: radial-gradient(rgba(244,234,214,0.10) 1.2px, transparent 1.2px); }

.nb-page ::selection { background: var(--pink); color: var(--ink); }

/* ══════════════ Nav ══════════════ */
.nb-nav {
    position: sticky; top: 0; z-index: 100;
    background: var(--bg);
    border-bottom: var(--border);
}
.nb-nav__inner {
    max-width: 1180px; margin: 0 auto;
    padding: 0.85rem 1.5rem;
    display: flex; align-items: center; justify-content: space-between;
    gap: 1rem; flex-wrap: wrap;
}
.nb-logo { display: inline-flex; align-items: center; gap: 0.65rem; text-decoration: none; color: var(--ink); }
.nb-logo__mark {
    display: inline-flex; align-items: center; justify-content: center;
    width: 2.1rem; height: 2.1rem;
    font-family: 'Unbounded', sans-serif; font-weight: 800; font-size: 1.15rem; line-height: 1;
    background: var(--pink); color: var(--on-accent);
    border: var(--border); border-radius: 8px;
    box-shadow: var(--shadow-sm);
    transform: rotate(-4deg);
}
.nb-logo__text { font-family: 'Unbounded', sans-serif; font-weight: 700; font-size: 1.15rem; letter-spacing: -0.02em; }

.nb-nav__links { display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap; }
.nb-navlink {
    display: inline-flex; align-items: center;
    height: 2.4rem; padding: 0 0.95rem;
    text-decoration: none; color: var(--on-accent);
    font-weight: 800; font-size: 0.85rem;
    background: var(--cyan);
    border: var(--border); border-radius: 9px;
    box-shadow: var(--shadow-sm);
    transition: transform 0.1s, box-shadow 0.1s;
}
.nb-navlink:hover { transform: translate(2px, 2px); box-shadow: 2px 2px 0 0 var(--ink); }
/* Register — primary action, kept distinct from the cyan nav buttons */
.nb-navlink--cta { background: var(--orange); }

.nb-toggle {
    width: 2.4rem; height: 2.4rem; cursor: pointer;
    font-size: 1.05rem; line-height: 1; color: var(--ink);
    background: var(--paper); border: var(--border); border-radius: 9px;
    box-shadow: var(--shadow-sm);
    transition: transform 0.1s, box-shadow 0.1s;
}
.nb-toggle:hover { transform: translate(2px, 2px); box-shadow: 2px 2px 0 0 var(--ink); }

/* ══════════════ Hero ══════════════ */
.nb-hero {
    max-width: 1180px; margin: 0 auto;
    padding: clamp(2.5rem, 6vw, 5rem) 1.5rem;
    display: grid; grid-template-columns: 1.15fr 0.85fr;
    gap: clamp(1.5rem, 5vw, 4rem); align-items: center;
}
.nb-hero__text .nb-badge   { animation: nb-rise 0.45s ease-out 0ms backwards; }
.nb-hero__text .nb-display { animation: nb-rise 0.55s cubic-bezier(0.22,1,0.36,1) 0.1s backwards; }
.nb-hero__text .nb-lede    { animation: nb-rise 0.5s ease-out 0.22s backwards; }
.nb-hero__text .nb-actions { animation: nb-rise 0.5s ease-out 0.38s backwards; }

.nb-badge {
    display: inline-block;
    font-family: 'Manrope', sans-serif; font-weight: 800;
    font-size: 0.72rem; letter-spacing: 0.08em; text-transform: uppercase;
    padding: 0.45em 0.85em;
    border: var(--border); border-radius: 999px;
    box-shadow: var(--shadow-sm);
}
.nb-badge--cyan { background: var(--cyan); color: var(--on-accent); }
.nb-badge--ink { background: var(--ink); color: var(--bg); }

.nb-display {
    margin: 1.4rem 0 1.6rem;
    font-family: 'Unbounded', sans-serif; font-weight: 900;
    line-height: 0.92; letter-spacing: -0.02em;
}
.nb-display__a { display: block; font-size: clamp(3.5rem, 13vw, 8rem); }
.nb-display__b {
    display: inline-flex; flex-wrap: wrap; gap: 0.12em;
    font-size: clamp(1.9rem, 7vw, 4.2rem); margin-top: 0.22em;
}
/* Bulgarian tricolour — white / green / red */
.nb-flag {
    display: inline-block;
    padding: 0.02em 0.18em;
    border: var(--border); border-radius: 9px;
    box-shadow: var(--shadow-sm);
    line-height: 1.04;
}
.nb-flag--white { background: #ffffff; color: var(--on-accent); }
.nb-flag--green { background: var(--flag-green); color: #ffffff; }
.nb-flag--red   { background: var(--flag-red);   color: #ffffff; }

.nb-lede {
    max-width: 30rem; font-size: 1.05rem; line-height: 1.7; font-weight: 500;
    margin-bottom: 2rem;
}
.nb-lede strong {
    font-family: 'Unbounded', sans-serif; font-weight: 700; font-size: 1.05em;
}
.nb-phon {
    display: inline-block; margin: 0 0.15em;
    font-weight: 800; color: var(--on-accent);
    background: var(--pink); padding: 0.05em 0.45em; border-radius: 6px;
    border: 2.5px solid var(--ink);
    transform: rotate(-2deg);
}
.nb-hl { box-shadow: inset 0 -0.5em 0 0 var(--cyan); font-weight: 700; }

.nb-actions { display: flex; gap: 0.9rem; flex-wrap: wrap; }
.nb-btn {
    font-family: 'Manrope', sans-serif; font-weight: 800; font-size: 0.98rem;
    text-decoration: none; color: var(--ink);
    padding: 0.85rem 1.6rem; border: var(--border); border-radius: 10px;
    box-shadow: var(--shadow);
    transition: transform 0.12s ease, box-shadow 0.12s ease;
}
.nb-btn:hover { transform: translate(3px, 3px); box-shadow: 3px 3px 0 0 var(--ink); }
.nb-btn:active { transform: translate(6px, 6px); box-shadow: none; }
.nb-btn--primary { background: var(--orange); color: var(--on-accent); }
.nb-btn--ghost { background: var(--paper); }
.nb-btn--lg { font-size: 1.1rem; padding: 1rem 2rem; }

/* Hero art cluster */
.nb-hero__art {
    position: relative; min-height: 260px;
    display: flex; align-items: center; justify-content: center;
}
.nb-tile {
    width: clamp(11rem, 22vw, 15rem); aspect-ratio: 1;
    background: var(--blue); color: var(--on-accent);
    border: var(--border); border-radius: 18px;
    box-shadow: var(--shadow);
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    gap: 0.3rem; transform: rotate(3deg);
    animation: nb-pop-in 0.55s cubic-bezier(0.34,1.56,0.64,1) 0.24s backwards;
}
.nb-tile__glyph { font-family: 'Unbounded', sans-serif; font-weight: 900; font-size: clamp(5rem, 12vw, 8rem); line-height: 1; }
.nb-tile__name { font-weight: 800; font-size: 0.72rem; letter-spacing: 0.22em; }

.nb-chip {
    position: absolute;
    font-weight: 800; font-size: 0.9rem; color: var(--on-accent);
    padding: 0.45rem 0.8rem; border: var(--border); border-radius: 999px;
    box-shadow: var(--shadow-sm);
}
.nb-chip--pink  { background: var(--pink);   top: 4%;   right: 2%;  transform: rotate(6deg);  animation: nb-pop-in 0.5s cubic-bezier(0.34,1.56,0.64,1) 0.34s backwards; }
.nb-chip--cyan  { background: var(--cyan);   bottom: 16%; left: -2%; transform: rotate(-7deg); animation: nb-pop-in 0.5s cubic-bezier(0.34,1.56,0.64,1) 0.46s backwards; }
.nb-chip--green { background: var(--green);  bottom: -2%; right: 12%; transform: rotate(4deg); animation: nb-pop-in 0.5s cubic-bezier(0.34,1.56,0.64,1) 0.58s backwards; }

/* ── Clickable cultural motifs ── */
.nb-motif {
    background: none; border: 0; padding: 0; margin: 0;
    font: inherit; color: inherit; cursor: pointer;
    -webkit-tap-highlight-color: transparent;
    transition: transform 0.16s ease, filter 0.16s ease;
}
.nb-motif:hover { filter: brightness(1.04); }
.nb-motif:focus-visible { outline: 3px dashed var(--ink); outline-offset: 5px; border-radius: 8px; }
.nb-marten.nb-motif:hover   { transform: rotate(-5deg) scale(1.07); }
.nb-marten.nb-motif:active  { transform: rotate(-5deg) scale(0.96); }
.nb-banitsa.nb-motif:hover  { transform: rotate(7deg) scale(1.07); }
.nb-banitsa.nb-motif:active { transform: rotate(7deg) scale(0.96); }
.nb-rakia.nb-motif:hover    { transform: rotate(-6deg) scale(1.07); }
.nb-rakia.nb-motif:active   { transform: rotate(-6deg) scale(0.96); }

/* ── Martenitsa (Пижо & Пенда) ── */
.nb-marten {
    position: absolute; top: -10px; left: 0; z-index: 4;
    display: flex; flex-direction: column; align-items: center;
    transform: rotate(-5deg); transform-origin: top center;
    animation: nb-pop-in 0.5s cubic-bezier(0.34,1.56,0.64,1) 0.48s backwards;
}
.nb-marten__cord {
    width: 7px; height: 32px;
    background: repeating-linear-gradient(45deg, var(--flag-red) 0 4px, #fff 4px 8px);
    border: 2px solid var(--ink); border-radius: 4px;
}
.nb-marten__dolls { display: flex; gap: 11px; margin-top: -1px; }
.nb-marten__doll { display: flex; flex-direction: column; align-items: center; }
.nb-marten__head {
    width: 20px; height: 20px; border-radius: 50%;
    border: 2.5px solid var(--ink);
}
.nb-marten__body {
    width: 17px; height: 16px; margin-top: -2px;
    border: 2.5px solid var(--ink); border-radius: 6px;
}
.nb-marten__legs {
    width: 15px; height: 9px; margin-top: 1px;
    background:
        linear-gradient(var(--ink) 0 0) 2px 0 / 2px 9px no-repeat,
        linear-gradient(var(--ink) 0 0) 7px 0 / 2px 9px no-repeat,
        linear-gradient(var(--ink) 0 0) 12px 0 / 2px 9px no-repeat;
}
.nb-marten__doll--white .nb-marten__head,
.nb-marten__doll--white .nb-marten__body { background: #fff; }
.nb-marten__doll--red .nb-marten__head,
.nb-marten__doll--red .nb-marten__body { background: var(--flag-red); }
/* Shared little label for the cultural motifs */
.nb-deco-tag,
.nb-marten__tag {
    font-family: 'Manrope', sans-serif; font-weight: 800;
    font-size: 0.56rem; letter-spacing: 0.06em; text-transform: uppercase;
    color: var(--on-accent); background: #fff;
    border: 2px solid var(--ink); border-radius: 5px;
    padding: 0.18em 0.45em; box-shadow: 2px 2px 0 0 var(--ink);
    white-space: nowrap;
}
.nb-marten__tag { margin-top: 6px; }

/* ── Banitsa (coiled cheese pastry) ── */
.nb-banitsa {
    position: absolute; top: 1.4rem; right: 1.6rem; z-index: 2;
    display: flex; flex-direction: column; align-items: center; gap: 6px;
    transform: rotate(7deg);
}
.nb-banitsa__pastry {
    width: 58px; height: 58px; border-radius: 50%;
    border: 3px solid var(--ink); box-shadow: var(--shadow-sm);
    background:
        radial-gradient(circle at 38% 34%, rgba(255,255,255,0.45), transparent 42%),
        repeating-radial-gradient(circle at 50% 50%,
            #f5cd7a 0 3px, #e0a043 3px 6px, #a96a20 6px 7.5px);
}

/* ── Rakia (fruit-brandy bottle) ── */
.nb-rakia {
    position: absolute; top: -42px; right: 30px; z-index: 3;
    display: flex; flex-direction: column; align-items: center; gap: 6px;
    transform: rotate(-6deg);
}
.nb-bottle { display: flex; flex-direction: column; align-items: center; }
.nb-bottle__cap {
    width: 12px; height: 8px;
    background: var(--ink); border: 2.5px solid var(--ink); border-radius: 3px 3px 0 0;
}
.nb-bottle__neck {
    width: 11px; height: 14px; margin-top: -1px;
    background: #d9952f; border: 2.5px solid var(--ink); border-bottom: 0;
}
.nb-bottle__body {
    position: relative; margin-top: -1px;
    width: 30px; height: 40px;
    border: 3px solid var(--ink); border-radius: 5px 5px 9px 9px;
    box-shadow: var(--shadow-sm);
    background: linear-gradient(to top, #d9952f 0 82%, #eab85f 82% 100%);
    display: flex; align-items: center; justify-content: center;
}
.nb-bottle__label {
    width: 22px; height: 15px;
    background: #fff; border: 2.5px solid var(--ink); border-radius: 2px;
}

/* ══════════════ Marquee ══════════════ */
.nb-marquee {
    background: var(--ink); color: var(--bg);
    border-top: var(--border); border-bottom: var(--border);
    overflow: hidden; padding: 0.7rem 0;
}
.nb-marquee__track {
    display: flex; width: max-content;
    animation: nb-scroll 28s linear infinite;
}
.nb-marquee__item {
    display: inline-flex; align-items: baseline; gap: 0.5rem;
    font-family: 'Unbounded', sans-serif; font-weight: 700; font-size: 1.05rem;
    padding: 0 0.4rem; white-space: nowrap;
}
.nb-marquee__en { font-family: 'Manrope', sans-serif; font-weight: 600; font-size: 0.85rem; opacity: 0.65; }
.nb-marquee__star { color: var(--cyan); padding: 0 0.6rem; }

/* ══════════════ How it works ══════════════ */
.nb-path { position: relative; max-width: 1180px; margin: 0 auto; padding: clamp(3rem, 7vw, 5.5rem) 1.5rem; }
.nb-path__head { display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; margin-bottom: 2.5rem; }
.nb-path__title {
    font-family: 'Unbounded', sans-serif; font-weight: 800;
    font-size: clamp(1.6rem, 4vw, 2.6rem); letter-spacing: -0.02em;
}

.nb-steps {
    list-style: none; padding: 0; margin: 0;
    display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem;
}
.nb-card {
    position: relative;
    background: var(--paper);
    border: var(--border); border-radius: var(--radius);
    box-shadow: var(--shadow);
    padding: 1.6rem 1.5rem 1.8rem;
    transition: transform 0.12s ease, box-shadow 0.12s ease;
}
.nb-card:hover { transform: translate(3px, 3px); box-shadow: 3px 3px 0 0 var(--ink); }
.nb-card--cyan { background: var(--cyan); }
.nb-card--pink   { background: var(--pink); }
.nb-card--blue   { background: var(--blue); }

.nb-card__num {
    display: inline-flex; align-items: center; justify-content: center;
    width: 3rem; height: 3rem; margin-bottom: 1rem;
    font-family: 'Unbounded', sans-serif; font-weight: 900; font-size: 1.15rem; color: var(--ink);
    background: var(--bg); border: var(--border); border-radius: 10px;
    box-shadow: var(--shadow-sm);
}
.nb-page:not(.dark) .nb-card__num { background: #fffaf0; }
.nb-card__title {
    font-family: 'Unbounded', sans-serif; font-weight: 700;
    font-size: 1.25rem; line-height: 1.15; color: var(--on-accent);
    margin-bottom: 0.35rem;
}
.nb-card__en {
    display: inline-block; margin-bottom: 0.9rem;
    font-weight: 800; font-size: 0.72rem; letter-spacing: 0.12em; text-transform: uppercase;
    color: var(--on-accent); opacity: 0.7;
}
.nb-card__text { font-weight: 500; font-size: 0.96rem; line-height: 1.6; color: var(--on-accent); }

/* CTA band */
.nb-cta-band {
    position: relative;
    margin-top: 2.5rem;
    background: var(--purple); color: var(--on-accent);
    border: var(--border); border-radius: var(--radius);
    box-shadow: var(--shadow);
    padding: clamp(1.5rem, 4vw, 2.5rem);
    display: flex; align-items: center; justify-content: space-between; gap: 1.5rem; flex-wrap: wrap;
}
.nb-cta-band__text { font-family: 'Unbounded', sans-serif; font-weight: 800; font-size: clamp(1.3rem, 3.5vw, 2rem); }
.nb-cta-band__text span { font-family: 'Manrope', sans-serif; font-weight: 600; font-size: 0.95rem; opacity: 0.7; }

/* ══════════════ Footer ══════════════ */
.nb-footer {
    border-top: var(--border);
    padding: 2.5rem 1.5rem 3rem; text-align: center;
    display: flex; flex-direction: column; align-items: center; gap: 1rem;
}
.nb-footer__text { font-weight: 500; font-size: 0.9rem; line-height: 1.8; }
.nb-footer__text strong { font-family: 'Unbounded', sans-serif; font-weight: 700; }

/* ══════════════ Info modal ══════════════ */
.nb-modal {
    position: fixed; inset: 0; z-index: 1000;
    display: flex; align-items: center; justify-content: center;
    padding: 1.5rem;
    background: rgba(20, 17, 11, 0.55);
    backdrop-filter: blur(2px);
}
.nb-modal__box {
    width: 100%; max-width: 30rem;
    max-height: 85vh; overflow-y: auto;
    background: var(--paper); color: var(--ink);
    border: var(--border); border-radius: var(--radius);
    box-shadow: 8px 8px 0 0 var(--ink);
    padding: 1.6rem 1.6rem 1.8rem;
}
.nb-modal__head {
    display: flex; align-items: flex-start; gap: 0.9rem;
    margin-bottom: 1.1rem; padding-bottom: 1.1rem;
    border-bottom: var(--border);
}
.nb-modal__icon {
    flex-shrink: 0; font-size: 1.7rem; line-height: 1;
    padding: 0.35rem 0.45rem;
    border: var(--border); border-radius: 11px;
    box-shadow: var(--shadow-sm);
}
.nb-modal__heading { flex: 1; padding-top: 0.1rem; }
.nb-modal__title {
    font-family: 'Unbounded', sans-serif; font-weight: 800;
    font-size: 1.45rem; letter-spacing: -0.02em; line-height: 1.05;
}
.nb-modal__bg { font-weight: 700; font-size: 0.85rem; opacity: 0.6; }
.nb-modal__close {
    flex-shrink: 0; width: 2.3rem; height: 2.3rem; cursor: pointer;
    font-weight: 800; font-size: 0.95rem; color: var(--on-accent);
    background: var(--pink); border: var(--border); border-radius: 9px;
    box-shadow: var(--shadow-sm);
    transition: transform 0.1s, box-shadow 0.1s;
}
.nb-modal__close:hover { transform: translate(2px, 2px); box-shadow: 2px 2px 0 0 var(--ink); }
.nb-modal__lead {
    font-family: 'Unbounded', sans-serif; font-weight: 700;
    font-size: 1.02rem; line-height: 1.4; margin-bottom: 1rem;
}
.nb-modal__p { font-weight: 500; font-size: 0.95rem; line-height: 1.65; margin-bottom: 0.85rem; }
.nb-modal__p:last-child { margin-bottom: 0; }

/* Modal transition */
.nb-modal-enter-active, .nb-modal-leave-active { transition: opacity 0.16s ease; }
.nb-modal-enter-from, .nb-modal-leave-to { opacity: 0; }
.nb-modal-enter-active .nb-modal__box,
.nb-modal-leave-active .nb-modal__box { transition: transform 0.18s cubic-bezier(.2,.9,.3,1.2); }
.nb-modal-enter-from .nb-modal__box { transform: translate(-6px, -10px) scale(0.94); }
.nb-modal-leave-to .nb-modal__box { transform: scale(0.96); }

/* ══════════════ Animations ══════════════ */
@keyframes nb-rise    { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }
@keyframes nb-pop-in  { from { opacity: 0; scale: 0.65; } to { opacity: 1; scale: 1; } }
@keyframes nb-scroll  { from { transform: translateX(0); } to { transform: translateX(-50%); } }

/* ══════════════ Scroll reveal ══════════════ */
/* Uses CSS individual transform properties (translate / scale) so they
   compose with element-level transform (rotate, hover translate) cleanly. */
.nb-reveal {
    opacity: 0;
    translate: 0 36px;
    transition:
        opacity 0.5s ease,
        translate 0.52s cubic-bezier(0.22, 1, 0.36, 1),
        scale    0.52s cubic-bezier(0.34, 1.56, 0.64, 1);
    transition-delay: var(--reveal-delay, 0ms);
}
.nb-reveal--pop  { scale: 0.82; translate: 0 20px; }
.nb-reveal--left { translate: -32px 0; }
.nb-reveal.nb-in-view { opacity: 1; translate: 0 0; scale: 1; }

@media (prefers-reduced-motion: reduce) {
    .nb-hero__text .nb-badge,
    .nb-hero__text .nb-display,
    .nb-hero__text .nb-lede,
    .nb-hero__text .nb-actions,
    .nb-tile, .nb-chip--pink, .nb-chip--cyan, .nb-chip--green, .nb-marten { animation: none; }
    .nb-marquee__track { animation: none; }
    .nb-modal-enter-active .nb-modal__box,
    .nb-modal-leave-active .nb-modal__box { transition: none; }
    .nb-modal-enter-from .nb-modal__box { transform: none; }
    .nb-reveal { opacity: 1; translate: 0 0; scale: 1; transition: none; }
}

/* ══════════════ Responsive ══════════════ */
@media (max-width: 900px) {
    .nb-hero { grid-template-columns: 1fr; }
    .nb-hero__art { order: -1; min-height: 220px; margin-bottom: 0.5rem; }
    .nb-steps { grid-template-columns: 1fr; }
}
@media (max-width: 560px) {
    .nb-nav__inner { justify-content: center; }
    .nb-actions .nb-btn { flex: 1; text-align: center; }
    .nb-cta-band { flex-direction: column; align-items: stretch; text-align: center; }
    .nb-cta-band .nb-btn { text-align: center; }
    .nb-banitsa { display: none; }
    .nb-rakia { top: -20px; right: 14px; }
}
</style>
