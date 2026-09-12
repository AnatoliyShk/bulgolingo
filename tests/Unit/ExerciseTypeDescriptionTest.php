<?php

namespace Tests\Unit;

use App\Enums\ExerciseType;
use App\Enums\LanguageCode;
use PHPUnit\Framework\TestCase;

class ExerciseTypeDescriptionTest extends TestCase
{
    public function test_the_description_is_english_when_no_language_is_given(): void
    {
        foreach (ExerciseType::cases() as $type) {
            $this->assertSame($type->getDescription(LanguageCode::EN), $type->getDescription());
        }
    }

    public function test_the_description_follows_the_requested_language(): void
    {
        $this->assertSame('Fill in the Blank', ExerciseType::FILL_IN_THE_BLANK->getDescription(LanguageCode::EN));
        $this->assertSame('Попълване на празното място', ExerciseType::FILL_IN_THE_BLANK->getDescription(LanguageCode::BG));
    }

    /**
     * A Bulgarian name that fell back to English, or two types sharing one
     * name, would still render without error, so every pairing is checked.
     */
    public function test_every_type_has_its_own_name_in_every_language(): void
    {
        foreach (LanguageCode::cases() as $language) {
            $names = array_map(fn (ExerciseType $type) => $type->getDescription($language), ExerciseType::cases());

            $this->assertSame($names, array_unique($names), "Duplicate {$language->value} description");
        }

        foreach (ExerciseType::cases() as $type) {
            $this->assertNotSame($type->getDescription(LanguageCode::EN), $type->getDescription(LanguageCode::BG));
            $this->assertMatchesRegularExpression('/\p{Cyrillic}/u', $type->getDescription(LanguageCode::BG));
        }
    }
}
