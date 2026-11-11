<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default content language
    |--------------------------------------------------------------------------
    |
    | The code of the language (a row of the `languages` table) that an
    | exercise or lexema is created in when none is named, and the language a
    | word-pair board translates into by default. When the two are the same
    | language, a board falls back to the content language as its translation.
    |
    */

    'default_language' => env('LEARNING_DEFAULT_LANGUAGE', 'bg'),

    'default_translation_language' => env('LEARNING_DEFAULT_TRANSLATION_LANGUAGE', 'en'),

    /*
    |--------------------------------------------------------------------------
    | Learner time zone
    |--------------------------------------------------------------------------
    |
    | Where a learner's day starts and ends for the streak and the profile's
    | "practised today" flame. Timestamps are still stored in the app time
    | zone (UTC); only the day boundaries are drawn here.
    |
    | shortcut: one zone for every learner, upgrade to a per-user timezone
    | column once learners outside Bulgaria matter.
    |
    */

    'timezone' => env('LEARNING_TIMEZONE', 'Europe/Sofia'),

];
