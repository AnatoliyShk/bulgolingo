<?php

namespace App\Enums;

use App\Models\User;

enum LearningPathType: string
{
    case Regular = 'regular';
    case Test = 'test';
    case Premium = 'premium';

    public function label(): string
    {
        return match ($this) {
            self::Regular => 'Regular',
            self::Test => 'Test',
            self::Premium => 'Premium',
        };
    }

    /**
     * The types a viewer is allowed to see, as column values ready for a
     * whereIn.
     *
     * Admins see every type, which is what keeps the `test` tier out of the
     * public catalog: the load-test tooling generates paths by the thousand and
     * tags them all as tests, so they exist for measurement without a student
     * ever being offered one. A premium account sees the premium tier on top of
     * the regular one; everyone below that, signed in or not, sees regular
     * alone. Regular is in every list, which is why an unauthenticated visitor
     * and a plain account resolve to the same thing.
     *
     * @return array<int, string>
     */
    public static function visibleTo(?User $user): array
    {
        if ($user?->isAdmin()) {
            return array_column(self::cases(), 'value');
        }

        if ($user?->type === UserType::Premium) {
            return [self::Regular->value, self::Premium->value];
        }

        return [self::Regular->value];
    }

    /**
     * Value/label pairs for the admin form's select, built from the cases so a
     * type added here reaches the form without a second edit.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $type) => ['value' => $type->value, 'label' => $type->label()],
            self::cases()
        );
    }
}
