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
