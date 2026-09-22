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
- Semantic search over exercises and learning paths (RAG)

## Spaced repetition (FSRS)
Scheduling uses FSRS-6. Every word a user has met carries two numbers:

- **Stability** — roughly how many days until the chance of recalling it falls to 90%.
- **Difficulty** — 1 to 10, governing how much each successful review grows that stability.

Rather than asking the user to rate their own recall, the grade is inferred from how they answered: a wrong answer is `Again`, a hint is `Hard`, a correct answer under three seconds is `Easy`, anything else is `Good`. `GradeLexemeReview` hands that to the scheduler, which updates both numbers and inverts the forgetting curve to find the next due date at the user's target retention (0.9 by default). Intervals get a small random fuzz so reviews don't pile up on a single day.

Every review is appended to `review_logs` with the memory state before and after it, so the 21 model parameters can be re-fitted against real answer history later.

## Semantic search (RAG)
Exercise and learning-path search is retrieval over embeddings, not keyword matching — no generation, just ranking existing content by meaning. Two features use it:

- **Exercise search** — looks up exercises directly by meaning.
- **Learning-path search** — the catalog page's search field narrows every section to the paths whose exercises are closest in meaning to the query, closest first.

Each exercise's `clause` is turned into a labelled text summary (word pairs, sentence/options/answer, explanation — the shape depends on the exercise type) and embedded via Gemini's `gemini-embedding-2` (768 dimensions) in a queued job, stored in a pgvector column with an HNSW index. A search query is embedded once and compared against those vectors; a learning path ranks by its single closest-matching exercise rather than an average, so one strongly relevant lesson is enough to surface the whole path.

Admins can turn search off or tune the similarity floor from the settings page. Turning it off hides the search UI, 404s the search endpoint, and stops every call to the embedding provider — including jobs already queued.

## Stack
PHP 8.5 · Laravel 13 · Inertia 2 · Vue 3 · SCSS · Vite · PostgreSQL 18 · Redis · RabbitMQ · Docker

## Running locally
```bash
git clone https://github.com/AnatoliyShk/bulgolingo
cd bulgolingo
cp .env.example .env
docker compose up -d
php artisan migrate --seed
```
The app requires PHP 8.5. The Sail container in `compose.yaml` runs PHP 8.5, so if the host has an older PHP, run PHP commands (`artisan`, `composer`, `pint`, `phpunit`) inside it with `docker compose exec laravel.test …`.

## Connecting an MCP client locally
The app runs an MCP server at `/mcp/content`. Its tools list and search the course catalogue: learning paths, lessons, exercises and desired topics. The endpoint needs a Passport login (`auth:api`), and `.mcp.json` in the repo root already registers it with Claude Code. There are two ways to sign in, and you only need one.

**OAuth (`balkanbuddy-content`).** This entry has no headers. The first time Claude Code connects, it opens a browser, you log in to the local app at `http://localhost:350`, and Claude Code keeps the token. Nothing needs to be set up beforehand.

**Personal access token (`balkanbuddy-local`).** Use this where no browser can open, such as scripts or CI. Issue a token for a user:
```bash
docker compose exec -u sail laravel.test php artisan app:mcp-token you@example.com
```
The command creates the user as an MCP client if the email doesn't exist yet, then prints a token with the `mcp:use` scope. Export it in the shell you start Claude Code from:
```bash
export BALKANBUDDY_LOCAL_TOKEN=<token>
claude
```
Claude Code reads `${BALKANBUDDY_LOCAL_TOKEN}` from its own environment, not from the app's `.env`.

Both entries point at the same endpoint. With both enabled, every tool shows up twice, so disable the one you don't use in `/mcp`.

Two more servers are in `.mcp.json`:
- `lesson-planner` (`http://localhost:8765/mcp`) is the separate Lesson Planner app. It only connects while that app is running.
- `balkanbuddy-cloud` is the production app. It reads `BALKANBUDDY_CLOUD_TOKEN`, and `BALKANBUDDY_CLOUD_URL` if you need a different URL.

Run `/mcp` in Claude Code to see which servers connected.

## Admin panel roles
The admin panel lives under `/admin` and is gated by the user's role. Each user belongs to exactly one row of the `roles` table (`users.role_id`); there are three to start with, and a new account is a `student`.

- **Student** (`student`) — the default; no admin panel access.
- **Full admin** (`admin`) — unrestricted: browsing and editing lessons, exercises, learning paths, bots, scripted dialogues, settings, and user records.
- **Admin visitor** (`admin_visitor`) — a read-only demo role for letting people click through the admin panel without risking real data or exposing other users' accounts:
  - Can browse every admin page (learning paths, lessons, exercises, bots, scripted dialogues/lines, settings, metrics, vitals, logs).
  - **Cannot** view the Users section at all — it's hidden from the panel nav and the `/admin/users` route itself returns 403 for this role.
  - **Cannot** make any change (create/update/delete, including settings) — any write request is blocked with a 403 explaining that the role is read-only.

Seed the read-only demo account with:
```bash
php artisan db:seed --class=AdminVisitorSeeder
```

Demo credentials:

| Field    | Value |
|----------|-------|
| Name     | `admin` |
| Email    | `admin@admin.com` |
| Password | `admin` |

Log in with these credentials, then visit `/admin` to explore.

## Running the mobile app locally
The app also ships as a native iOS/Android build via [NativePHP for Mobile](https://nativephp.com/docs/mobile).

**Prerequisites**
- PHP 8.5 on the host — it must match the embedded runtime pinned in `nativephp.lock`, or `./native run` stops.
- Android: [Android Studio](https://developer.android.com/studio) (SDK + a JDK) — Linux, macOS, or Windows. Not supported under WSL.
- iOS: Xcode + CocoaPods — macOS only.

**Setup**
```bash
composer install
cp .env.example .env
php artisan key:generate
./native install        # or: ./native install android / ./native install ios
```
`native:install` prompts for a `NATIVEPHP_APP_ID` (written to `.env`) and downloads the embedded PHP runtime pinned in `nativephp.lock` (PHP 8.5), writing the exact patch release it installed back to the lock.

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
