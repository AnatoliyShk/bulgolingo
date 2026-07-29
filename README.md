# Bulgolingo

Web application for learning Bulgarian vocabulary and grammar.
Built with PHP and Laravel.

Backend API: [bulgolingo-api](https://github.com/AnatoliyShk/bulgolingo-api)

## Features
- Lesson and vocabulary browsing
- Spaced-repetition practice sessions
- User progress tracking

## Stack
PHP 8 · Laravel · Vue.js · MySQL · Vite · Docker

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
