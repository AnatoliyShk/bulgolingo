# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

### Development (all services in one terminal)
```bash
composer dev
```
Starts `php artisan serve`, queue worker, `pail` log viewer, the scheduler (`schedule:work`), and `yarn dev` concurrently.

### First-time setup
```bash
composer setup
```
Installs dependencies, copies `.env`, generates app key, runs migrations, and builds frontend assets.

### Frontend only
```bash
yarn dev    # Vite HMR dev server
yarn build  # Production build
```

### Testing
Run the suite against the **local Sail Postgres container**. `phpunit.xml` sets
`DB_DATABASE=testing` but never `DB_CONNECTION`, so a bare `composer test` or
`php artisan test` resolves to whatever `.env` points at — currently the hosted
production database, not a test one. Always pass the overrides:

```bash
docker compose exec -u sail \
  -e DB_CONNECTION=pgsql -e DB_HOST=pgsql -e DB_DATABASE=testing \
  -e QUEUE_CONNECTION=sync -e CACHE_STORE=array -e SESSION_DRIVER=array -e TELESCOPE_ENABLED=false \
  laravel.test ./vendor/bin/phpunit --no-coverage

# same prefix, narrowed to one file or one test:
#   laravel.test ./vendor/bin/phpunit tests/Feature/Foo.php
#   laravel.test ./vendor/bin/phpunit --filter=TestName
```

The local Postgres volume was initialised from `.env`'s `DB_USERNAME` /
`DB_PASSWORD` (role `laravel`), so the container's own env already
authenticates and only the host, connection and database need overriding. Its
`testing` database is created by Sail's init script. Inspect it with
`docker compose exec pgsql psql -U laravel -d testing`. If the volume is ever
recreated with other credentials, pass `-e DB_USERNAME=… -e DB_PASSWORD=…` too.

Tests that touch no database — those extending `PHPUnit\Framework\TestCase`
rather than `Tests\TestCase` — can run on the host directly, because `phpunit.xml`
bootstraps only the autoloader and no app boots. That needs PHP 8.5 on the host:
Composer's platform check rejects anything older before a test loads.

```bash
./vendor/bin/phpunit tests/Unit/LoadTestGeneratorTest.php
```

Always run as the `sail` user (`-u sail`); a root-run container leaves files the
host user cannot read. If `Admin\ExerciseTest` errors on
`storage/framework/testing/disks`, that is the cause:
`docker compose exec laravel.test chown -R sail:sail storage/framework/testing`.

`WWWUSER`/`WWWGROUP` are now set, so the container's `sail` is **UID 1000** — the
same as the host user, which is why `-u sail` writes host-readable files. Files
left from the earlier UID 1337 default are *not* writable by the server and fail
with "could not be opened in append mode"; `storage/` and `bootstrap/cache` were
chowned to 1000 on 2026-09-23, but anything restored from an old backup needs the
same treatment.

### Code style
```bash
./vendor/bin/pint         # auto-fix PHP style (Laravel Pint)
```
Pint has to run on PHP 8.5 — an older PHP stops with a parse error on 8.5
syntax such as the pipe operator (`|>`). Without 8.5 on the host, run it in the
container as `sail`, which shares the host user's UID and so can rewrite project
files:

```bash
docker compose exec -u sail laravel.test ./vendor/bin/pint --dirty
```

The one exception is `config/telescope.php`, still owned by root; chown it to
1000 before Pint can touch it.

### Migrations & DB
```bash
php artisan migrate
php artisan migrate:fresh --seed   # wipe and reseed
php artisan tinker
```

A migration that only adds a unique index to an existing table is named
`add_unique_index_to_<table>`, e.g. `add_unique_index_to_messengers`. When the
table already has one, or the index is on a single column, append that column:
`add_unique_index_to_lexemas_word`. The migration first resolves any rows that
would break the index (merge or renumber them), because Postgres refuses to
build a unique index over existing duplicates.

Exercise images and avatars are stored on the `bb_images` disk (`Images::DISK`),
an S3-compatible bucket configured by the `AWS_*` env vars, and served through
one-hour `temporaryUrl()`s — not from `storage/app/public`.

## Architecture Overview

**Stack:** PHP 8.5 + Laravel 13 + Inertia.js + Vue 3 (Composition API) + Tailwind CSS. The app is a Bulgarian language-learning platform (Duolingo-style).

### Request lifecycle
Every page render goes through Inertia: Laravel returns `Inertia::render('PageName', [...props])`, Vite bundles the Vue SPA, and `HandleInertiaRequests` middleware injects shared props (`auth.user`, `auth.isAdmin`, `auth.isAdminVisitor` — both derived from the user's role) available in every Vue page via `usePage()`.

### Route structure
- `/` — public welcome page
- `/profile` — authenticated user profile
- `/learning-paths` — browse & start learning paths
- `/exercise/{id}` — exercise player (student-facing)
- `/lesson/{id}` — lesson view
- `/stats` — stats dashboard (uses `vue-data-ui` charts)
- `/admin/*` — admin panel (guarded by `EnsureIsAdmin` middleware; requires the `admin` or `admin_visitor` role; `RestrictAdminVisitor` keeps visitors read-only and out of `/admin/users`)

### Domain model
```
LearningPath ──< learning_path_lesson >── Lesson ──< exercise_lesson (order) >── Exercise ──< exercise_image >── Images
     │                                                                            │
     └──< learning_path_user >── User ──< user_exercise_completions >─────────────┤
                                  │                                               │
                                  ├──< user_lexema (FSRS state) >── Lexema ──< exercise_lexema
                                  ├──< review_logs >── Lexema
                                  ├──< messengers (messenger_name, messenger_user_id)
                                  ├──< desired_topics
                                  ├── Role (student | admin | admin_visitor)
                                  └── Type
```

- **Exercise** is the core unit. Its `clause` column stores a JSON blob whose schema is determined by `decision_type` (an `ExerciseType` enum). `ExerciseObserver` validates `clause` against `ExerciseType::dataRules()` on `creating`/`updating`, and on Postgres per-type CHECK constraints on `clause` back it up.
- **ExerciseType** enum (`app/Enums/ExerciseType.php`) defines four types: `multiple_choice`, `true_false`, `fill_in_the_blank`, `image_matching`. Each type has its own `clause` shape and validation rules, and `ExerciseType::options()` gives the value/label pairs every exercise-type select uses, each with the type's blank clause (`ExerciseType::defaultClause()`). The admin `Components/Forms/ExerciseForm.vue` (create and edit) builds every clause from that, so the frontend defines no clause shape of its own. Both `dataRules()` and `defaultClause()` are derived from `ExerciseType::clauseFields()`, which lists every clause key once with its rules and blank value (`App\Support\ClauseField`): change a shape there and nowhere else.
- **Lesson ordering**: a lesson's exercises run in `exercise_lesson.order`, unique per lesson; append with `Lesson::attachExerciseAtEnd()`, which locks the lesson row. Lessons within a path run in lesson-id order.
- **Completion** is per user, not a stored flag: a row in `user_exercise_completions` marks an exercise done, and a lesson is complete when every one of its exercises is (`App\Services\ProgressService`, which also builds each enrolled path's progress and advances the day streak in `recordPractice()`). `ProgressService::completeExercise()` is the single entry point for a completion: it records the row (`UserExerciseCompletion::record()`, which also syncs the stats caches), queues `ExperienceCountUpdate` and `LexemaReviewGrade` on the `learning_path` queue, and advances the streak. Its counterpart for taking progress back is `ProgressService::resetLesson()` / `resetLearningPath()`, which go through `UserExerciseCompletion::clear()` so the stats caches are synced for every removed row; never delete from `user_exercise_completions` with a bare query. The next exercise to play comes from `Lesson::nextIncompleteExerciseId()`.
- **Cache services** (`App\Services\*CacheService`, `CompletionCacheSyncService`) are instance singletons that take their `Repository` by constructor; `AppServiceProvider::register()` is the one place that hands the stats/metrics ones the `redis` store (`ExerciseActivityCacheService` needs a real Redis store: it uses hashes and Lua). Inject them, or `app(...)` from a model; in tests, `$this->mock()` a service or rebind its store with `$this->app->when(...)->needs(Repository::class)`.
- **Images** are attached through the `exercise_image` pivot, unique per `(exercise_id, image_id)`. The admin controller uploads, replaces and deletes the files on the `bb_images` disk.
- **Role**: every user belongs to one row of `roles` via `users.role_id`. The rows are inserted by the migration that creates the table, one per `RoleName` enum case, and `Role::named(RoleName::Admin)` looks one up. A user created without a role becomes a `student` (a `creating` hook on `User`). Check roles with `$user->isAdmin()`, `$user->isAdminVisitor()` or `$user->hasRole(...)`; in tests use the `UserFactory` states `admin()` and `adminVisitor()`.
- **Lexemas**: `ExerciseObserver` links each exercise to the lexemas of its Cyrillic option words (`Exercise::syncLexemasFromOptions()`, backfilled by `BackfillLexemasFromExerciseOptions`). Per user, `user_lexema` holds the FSRS memory state (`stability`, `difficulty`, `state`, `due_at`, `reps_total`, `lapses`), and each grading appends a `review_logs` row; the `LexemaReviewGrade` job writes both through `App\Services\GradeLexemeReviewService`.
- **Messengers** link a user to an external chat account. `messenger_name` is a `MessengerName` enum (`telegram`, `whatsapp`, `viber`), held to that list by a Postgres CHECK, and `(messenger_name, messenger_user_id)` is unique.

### LLM tracing (Langfuse)
Every Laravel AI SDK run is traced to Langfuse over OpenTelemetry (OTLP/JSON to `/api/public/otel`). `App\Ai\Tracing\LangfuseTracer` listens to the SDK's events: an agent run is an `agent` observation, each model step a `generation`, each tool call a sibling `tool`, and embeddings are `embedding` observations. `LangfuseServiceProvider` exports after the response and after each queue job, and only when `LANGFUSE_PUBLIC_KEY`/`LANGFUSE_SECRET_KEY` are set; `phpunit.xml` sets `LANGFUSE_ENABLED=false`. New agents and tools are traced without extra code; mark a tool with `#[TraceAs('retriever')]` (or another Langfuse type) when `tool` is too generic. Emails and phone numbers in inputs/outputs are masked (`LANGFUSE_MASK_PII`).

### Admin vs student controllers
There are two `ExerciseController` classes:
- `App\Http\Controllers\Admin\ExerciseController` — CRUD for admins, including image management.
- `App\Http\Controllers\ExerciseController` — student-facing; `show` renders the exercise player, `complete` marks it done.

### Frontend conventions
- Pages live in `resources/js/Pages/` mirroring the Inertia render string.
- Shared reusable components are in `resources/js/Components/`.
- The single composable `useTheme` (`resources/js/composables/useTheme.js`) manages a module-level `ref` for dark/light mode, persisted in `localStorage`. Default is `dark`.
- `vue-data-ui` is used for charts on the Stats page.
- Ziggy is included for named route helpers (`route('name', params)`) in Vue via `@inertiajs/vue3`.

## Playwright e2e tests
Specs live in `e2e/`; CI (`.github/workflows/playwright.yml`) runs the suite on
chromium and webkit against a fresh database seeded by `E2eSeeder`, with the
fixture ids exported by `php artisan e2e:fixture-env`. Every failure so far has
been a spec drifting behind the UI rather than a real regression, so:

- Scope an assertion about shared chrome to its container — `page.locator('.nb-topbar').getByRole('link', { name: 'Profile' })`, never a bare `getByRole`. Role-name matching is substring and case-insensitive, so the leaderboard's "View profile" links match `Profile` and the profile card's "view your stats" link matches `Stats`, and either turns a passing assertion into a strict-mode violation. Use `exact: true` where no container fits.
- Assert a page title by heading level or by its BEM class, not by copy that has to match verbatim — the text comes from the controller's Inertia props and is reworded there.
- Grep for a class before locating by it. BEM names move in refactors (`.nb-path-list__path` became `.nb-path-list__path-wrapper`), and a locator matching nothing does not always fail.
- A branch on `count() === 0` has to be reachable both ways. A stale locator pins it to the empty-state branch, and the test keeps passing while asserting nothing; check against the seeded data that the populated branch still runs.
- Adding a section to a page means updating the specs that count its siblings — the `toHaveCount` on `.nb-stats__section` and every loop over that set. Give the newcomer its own class when it does not share the shape the loop expects.

## Styles
- Never use <style> blocks in Vue components
- All styles go in assets/scss/components/_component-name.scss
- Use BEM naming
- Use the `useTheme` composable to manage dark/light mode
- Use the `usePage` composable to access props

### Vue Component
When creating a Vue component always:
- Create `ComponentName.vue` (no <style> block)
- Create `assets/scss/components/_component-name.scss`
- Import the scss file in the vue file
- Use BEM naming
- Use the `useTheme` composable to manage dark/light mode
- Use the `usePage` composable to access props
- If component in Admin panel (guarded by `EnsureIsAdmin` middleware),
- If component in Admin panel save styles in `assets/scss/components/admin/_admin-component-name.scss`
- Add playwright tests for the component. 100% coverage is required.
- Do not use → symbol in UI at all.

### Infrastructure
The Docker Compose setup (`compose.yaml`) uses Laravel Sail's **PHP 8.5** runtime (`vendor/laravel/sail/runtimes/8.5`) with **PostgreSQL 18** and **Redis**. The NativePHP mobile build embeds PHP 8.5 too, pinned in `nativephp.lock`. The local dev default (without Docker) uses **SQLite** (`database/database.sqlite`). Queue driver defaults to `database`; jobs are dispatched for word count updates.

## Comments
- No explanatory comments inside function bodies
- The reasoning goes above the function — a PHP docblock, or a `//` block above the `function` / arrow-function / Playwright `test(...)` line
- Phrase it as a description of the whole function, not of one line
- When editing a function that has body comments, merge them into the top comment rather than deleting them
- Laravel's scaffolded empty `//` placeholders (observer stubs, unused controller actions) are not comments in this sense — leave them
