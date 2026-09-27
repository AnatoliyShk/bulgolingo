<?php

namespace App\Enums;

/**
 * The messengers an account can be linked through. Stored lowercase, so the
 * (messenger_name, messenger_user_id) unique index cannot be dodged by
 * spelling one messenger two ways. The messengers_messenger_name_check
 * constraint holds the same list, so a case added here needs a migration that
 * widens it.
 */
enum MessengerName: string
{
    case Telegram = 'telegram';
    case WhatsApp = 'whatsapp';
    case Viber = 'viber';

    public function label(): string
    {
        return match ($this) {
            self::Telegram => 'Telegram',
            self::WhatsApp => 'WhatsApp',
            self::Viber => 'Viber',
        };
    }

    /**
     * Value/label pairs for the admin form's select, built from the cases so a
     * messenger added here reaches the form without a second edit.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $name) => ['value' => $name->value, 'label' => $name->label()],
            self::cases()
        );
    }
}
