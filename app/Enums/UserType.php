<?php

namespace App\Enums;

enum UserType: string
{
    case Regular = 'regular';
    case Playwright = 'playwright';
    case Filler = 'filler';
    case Premium = 'premium';
}
