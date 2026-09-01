# Balkanbuddy

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
