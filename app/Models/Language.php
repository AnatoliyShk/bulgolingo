<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A language the app teaches or translates into. The rows are inserted by the
 * migration that creates the table, and adding a language is one more row:
 * nothing in the code names one except the defaults in config/learning.php.
 * `unicode_script` is the Unicode script name (`Cyrillic`, `Latin`) that tells a
 * word of the language from a word of another.
 */
#[Fillable(['code', 'name', 'unicode_script'])]
class Language extends Model
{
    public static function idFor(string $code): int
    {
        return (int) static::where('code', $code)->firstOrFail()->id;
    }

    /**
     * The language content is created in when none is named.
     */
    public static function defaultId(): int
    {
        return static::idFor(config('learning.default_language'));
    }

    /**
     * The language a word-pair board translates $languageId into when none is
     * named: the configured default, or the content default when the board
     * teaches the translation language itself.
     */
    public static function defaultTranslationIdFor(int $languageId): int
    {
        $translation = static::idFor(config('learning.default_translation_language'));

        return $translation === $languageId ? static::defaultId() : $translation;
    }

    /**
     * Every language as {id, code, name}, alphabetical, for the admin forms'
     * language pickers.
     *
     * @return Collection<int, Language>
     */
    public static function pickerOptions(): Collection
    {
        return static::orderBy('name')->get(['id', 'code', 'name']);
    }

    /**
     * What the admin exercise form needs to offer a choice of language: every
     * language, and the ids a new exercise starts with (the default content
     * language and its default translation language).
     *
     * @return array{languages: Collection<int, Language>, defaults: array{language_id: int, translation_language_id: int}}
     */
    public static function formOptions(): array
    {
        $language = static::defaultId();

        return [
            'languages' => static::pickerOptions(),
            'defaults' => [
                'language_id' => $language,
                'translation_language_id' => static::defaultTranslationIdFor($language),
            ],
        ];
    }

    public function exercises(): HasMany
    {
        return $this->hasMany(Exercise::class);
    }
}
