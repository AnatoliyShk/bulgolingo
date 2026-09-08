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
rather than `Tests\TestCase` — run on the host directly, because `phpunit.xml`
bootstraps only the autoloader and no app boots:

```bash
./vendor/bin/phpunit tests/Unit/LoadTestGeneratorTest.php
```

Always run as the `sail` user (`-u sail`); a root-run container leaves files the
host user cannot read. If `Admin\ExerciseTest` errors on
`storage/framework/testing/disks`, that is the cause:
`docker compose exec laravel.test chown -R sail:sail storage/framework/testing`.

### Code style
```bash
./vendor/bin/pint         # auto-fix PHP style (Laravel Pint)
```

### Migrations & DB
```bash
php artisan migrate
php artisan migrate:fresh --seed   # wipe and reseed
php artisan tinker
```

Image uploads are stored in `storage/app/public` and served via `storage:link`. The public disk is used throughout — call `php artisan storage:link` after setup if images are missing.

## Architecture Overview

**Stack:** Laravel 13 + Inertia.js + Vue 3 (Composition API) + Tailwind CSS. The app is a Bulgarian language-learning platform (Duolingo-style).

### Request lifecycle
Every page render goes through Inertia: Laravel returns `Inertia::render('PageName', [...props])`, Vite bundles the Vue SPA, and `HandleInertiaRequests` middleware injects shared props (`auth.user`, `auth.isAdmin`) available in every Vue page via `usePage()`.

### Route structure
- `/` — public welcome page
- `/profile` — authenticated user profile
- `/learning-paths` — browse & start learning paths
- `/exercise/{id}` — exercise player (student-facing)
- `/lesson/{id}` — lesson view
- `/stats` — stats dashboard (uses `vue-data-ui` charts)
- `/admin/*` — admin panel (guarded by `EnsureIsAdmin` middleware; requires `is_admin = true` on the user)

### Domain model
```
LearningPath ──< learning_path_lesson >── Lesson ──< Exercise
     │                                       │
     └──< learning_path_user >── User        └── (is_completed bool)
                │
                └──< user_learned_word >── Lexemas
```

- **Exercise** is the core unit. Its `clause` column stores a JSON blob whose schema is determined by `decision_type` (an `ExerciseType` enum). The `Exercise` model validates `clause` against `ExerciseType::dataRules()` in a `saving` model hook.
- **ExerciseType** enum (`app/Enums/ExerciseType.php`) defines five types: `multiple_choice`, `true_false`, `fill_in_the_blank`, `image_matching`, `bot_dialog`. Each type has its own `clause` shape and validation rules.
- **Lesson** tracks aggregate completion (`refreshCompletionStatus()`) by checking whether all child exercises are completed.
- **Images** are stored via a many-to-many pivot (`exercise_image`) so an exercise can have associated images. The admin controller handles upload/replace/delete of the physical file on the `public` storage disk.
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
The Docker Compose setup (`compose.yaml`) uses Laravel Sail with **PostgreSQL 18** and **Redis**. The local dev default (without Docker) uses **SQLite** (`database/database.sqlite`). Queue driver defaults to `database`; jobs are dispatched for word count updates.

## Comments
- No explanatory comments inside function bodies
- The reasoning goes above the function — a PHP docblock, or a `//` block above the `function` / arrow-function / Playwright `test(...)` line
- Phrase it as a description of the whole function, not of one line
- When editing a function that has body comments, merge them into the top comment rather than deleting them
- Laravel's scaffolded empty `//` placeholders (observer stubs, unused controller actions) are not comments in this sense — leave them
