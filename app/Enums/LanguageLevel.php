<?php

namespace App\Enums;

/**
 * CEFR proficiency levels, lowest to highest. The case order is the level
 * order, so cases() doubles as the sorted list for a select.
 */
enum LanguageLevel: string
{
    case A1 = 'A1';
    case A2 = 'A2';
    case B1 = 'B1';
    case B2 = 'B2';
    case C1 = 'C1';
    case C2 = 'C2';

    public function label(): string
    {
        return match ($this) {
            self::A1 => 'A1 Beginner',
            self::A2 => 'A2 Elementary',
            self::B1 => 'B1 Intermediate',
            self::B2 => 'B2 Upper intermediate',
            self::C1 => 'C1 Advanced',
            self::C2 => 'C2 Proficient',
        };
    }

    /**
     * Value/label pairs for the admin form's select, built from the cases so a
     * level added here reaches the form without a second edit.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $level) => ['value' => $level->value, 'label' => $level->label()],
            self::cases()
        );
    }
}
