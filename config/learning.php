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

];
