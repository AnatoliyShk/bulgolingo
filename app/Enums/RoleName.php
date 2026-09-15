<?php

namespace App\Enums;

enum RoleName: string
{
    case Student = 'student';
    case Admin = 'admin';
    case AdminVisitor = 'admin_visitor';

    public function label(): string
    {
        return match ($this) {
            self::Student => 'Student',
            self::Admin => 'Admin',
            self::AdminVisitor => 'Admin visitor',
        };
    }
}
