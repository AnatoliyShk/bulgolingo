<?php

namespace App\Enums;

/**
 * ISO 639-1 codes, lowercase to match what learning_paths.language stores.
 */
enum LanguageCode: string
{
    case BG = 'bg';
    case EN = 'en';
}
