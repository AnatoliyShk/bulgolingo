# BalkanBuddy

**Live at [balkanbuddy.laravel.cloud](https://balkanbuddy.laravel.cloud/)**

Web application for learning Bulgarian vocabulary and grammar.

A Laravel 13 backend serves a Vue 3 single-page frontend over Inertia 2.

The frontend is built with Vite; component styles are hand-written BEM SCSS,
and the stats dashboard uses vue-data-ui and ApexCharts. Playwright covers the
components end to end. The same codebase also ships as a native iOS and Android
build through NativePHP for Mobile.

Backend API: [bulgolingo-api](https://github.com/AnatoliyShk/bulgolingo-api)

## Features
- Lesson and vocabulary browsing
- Spaced-repetition practice sessions
- User progress tracking

## Spaced repetition (FSRS)
Scheduling uses FSRS-6. Every word a user has met carries two numbers:

- **Stability** — roughly how many days until the chance of recalling it falls to 90%.
- **Difficulty** — 1 to 10, governing how much each successful review grows that stability.

Rather than asking the user to rate their own recall, the grade is inferred from how they answered: a wrong answer is `Again`, a hint is `Hard`, a correct answer under three seconds is `Easy`, anything else is `Good`. `GradeLexemeReview` hands that to the scheduler, which updates both numbers and inverts the forgetting curve to find the next due date at the user's target retention (0.9 by default). Intervals get a small random fuzz so reviews don't pile up on a single day.

Every review is appended to `review_logs` with the memory state before and after it, so the 21 model parameters can be re-fitted against real answer history later.

## Stack
PHP 8.3 · Laravel 13 · Inertia 2 · Vue 3 · SCSS · Vite · PostgreSQL 18 · Redis · RabbitMQ · Docker

## Running locally
```bash
git clone https://github.com/AnatoliyShk/bulgolingo
cd bulgolingo
cp .env.example .env
docker compose up -d
php artisan migrate --seed
```

## Running the mobile app locally
The app also ships as a native iOS/Android build via [NativePHP for Mobile](https://nativephp.com/docs/mobile).

**Prerequisites**
- Android: [Android Studio](https://developer.android.com/studio) (SDK + a JDK) — Linux, macOS, or Windows. Not supported under WSL.
- iOS: Xcode + CocoaPods — macOS only.

**Setup**
```bash
composer install
cp .env.example .env
php artisan key:generate
./native install        # or: ./native install android / ./native install ios
```
`native:install` prompts for a `NATIVEPHP_APP_ID` (written to `.env`) and downloads the embedded PHP runtime pinned in `nativephp.lock`.

**Run**
```bash
./native run android    # or: ./native run ios
./native run android --watch   # hot reload on file changes (requires Watchman)
```

**Other useful commands**
```bash
./native emulator android   # list/launch an emulator or simulator
./native debug               # print environment info (SDKs, embedded PHP, plugins)
```

## Load testing
The schema is small enough that every query looks fast against development data
and stops being fast at production volume. `loadtest:seed` and its siblings
generate a realistic amount of history — users, content, completions and FSRS
review logs — measure what the hot queries actually do against it, and remove it
again. `loadtest:run` drives the whole sequence and writes the findings to a
file.

```bash
php artisan loadtest:run --tier=small --connection=redis --explain
```

That runs five stages in the only order the numbers mean anything in: seed, then
`pg_stat_reset()` (after the seeder's own writes, so its bulk COPY traffic is not
counted against the queries under test), then queue load, then measurement, then
an optional teardown. Each stage's transcript is captured rather than printed,
and the report lands at `storage/app/private/loadtest/<run-id>-report.md` with the
target host, per-stage timings, the rows written and the query plans.

| option | default | |
|---|---|---|
| `--tier` | `small` | `small`, `medium` or `large` |
| `--jobs` | `10000` | queue jobs to dispatch; `0` skips that stage |
| `--connection` | configured | queue connection for the load stage |
| `--explain` | off | include the full `EXPLAIN ANALYZE` for each hot query |
| `--teardown` | off | remove the generated data once the report is written |
| `--path` | manifest dir | write the report somewhere else |
| `--force` | off | skip the confirmation |

Sizes, at the default two review logs per learned word:

| tier | users | completions | on disk |
|---|---|---|---|
| small | 5,000 | ~149,000 | ~0.2 GB |
| medium | 25,000 | ~1,000,000 | ~1.3 GB |
| large | 100,000 | ~4,300,000 | ~5.5 GB |

Each user's fan-out is drawn per user rather than fixed: a lopsided share split
gives 5% of users the bulk of the rows and 75% almost none, with a ±50% jitter on
both completions and enrolments. That unevenness is the point — every index looks
selective when every user carries the same number of rows.

**Before running one**

- It writes to whichever database is configured, so check `php artisan db:show`
  first. There is a confirmation showing the host, and the large tier makes you
  type the database name.
- Set `TELESCOPE_ENABLED=false`. Telescope records every query these stages make.
- Pass `--connection` explicitly if `QUEUE_CONNECTION` resolves to `sync`, or the
  jobs run inline and the throughput figure measures the seeding process rather
  than a worker. The command warns when it detects this.
- Generated rows stay identifiable even without the manifest: content is named
  `[loadtest] …` and users get `@loadtest.invalid` addresses.
- Under Docker, run it as the container's own user — `./vendor/bin/sail artisan
  loadtest:run …`, not `docker compose exec`, which runs as root. The project is
  bind-mounted, so the report appears in `storage/app/private/loadtest/` either
  way, but anything root writes there is left owned by root and unreadable from
  the host.

The stages are also available on their own — `loadtest:seed`, `loadtest:queue`,
`loadtest:report` and `loadtest:teardown`, the last of which lists recorded runs
when called with no argument.
