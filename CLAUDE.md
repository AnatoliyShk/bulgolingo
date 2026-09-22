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
  -e DB_CONNECTION=pgsql -e DB_HOST=pgsql -e DB_DATABASE=testing -e DB_USERNAME=sail -e DB_PASSWORD=password \
  -e QUEUE_CONNECTION=sync -e CACHE_STORE=array -e SESSION_DRIVER=array -e TELESCOPE_ENABLED=false \
  laravel.test ./vendor/bin/phpunit --no-coverage

# same prefix, narrowed to one file or one test:
#   laravel.test ./vendor/bin/phpunit tests/Feature/Foo.php
#   laravel.test ./vendor/bin/phpunit --filter=TestName
```

The local database is stock Sail — user `sail`, password `password`, database
`testing` created by the image's init script. The `.env` credentials are the
cloud ones and do not authenticate against it.

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

Image uploads are stored in `storage/app/public` and served via `storage:link`. The public disk is used throughout — call `php artisan storage:link` after setup if images are missing.

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
LearningPath ──< learning_path_lesson >── Lesson ──< Exercise
     │                                       │
     └──< learning_path_user >── User        └── (is_completed bool)
                │
                ├──< user_learned_word >── Lexemas
                │
                └── Role (student | admin | admin_visitor)
```

- **Exercise** is the core unit. Its `clause` column stores a JSON blob whose schema is determined by `decision_type` (an `ExerciseType` enum). The `Exercise` model validates `clause` against `ExerciseType::dataRules()` in a `saving` model hook.
- **ExerciseType** enum (`app/Enums/ExerciseType.php`) defines five types: `multiple_choice`, `true_false`, `fill_in_the_blank`, `image_matching`, `bot_dialog`. Each type has its own `clause` shape and validation rules.
- **Lesson** tracks aggregate completion (`refreshCompletionStatus()`) by checking whether all child exercises are completed.
- **Images** are stored via a many-to-many pivot (`exercise_image`) so an exercise can have associated images. The admin controller handles upload/replace/delete of the physical file on the `public` storage disk.
- **Role**: every user belongs to one row of `roles` via `users.role_id`. The rows are inserted by the migration that creates the table, one per `RoleName` enum case, and `Role::named(RoleName::Admin)` looks one up. A user created without a role becomes a `student` (a `creating` hook on `User`). Check roles with `$user->isAdmin()`, `$user->isAdminVisitor()` or `$user->hasRole(...)`; in tests use the `UserFactory` states `admin()` and `adminVisitor()`.
- **Lexemas** are tracked per-user via a `user_lexema` pivot with an `reps_total` column. The `LearnedWordCountUpdate` job (currently a stub) is intended to update these counts.

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
