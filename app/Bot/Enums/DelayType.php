<?php

declare(strict_types=1);

namespace App\Bot\Enums;

enum DelayType: int
{
    case SECONDS = 0;

    case MINUTES = 1;

    case HOURS = 2;

    case DAYS = 3;

    public function toString(): string
    {
        return match ($this) {
            self::SECONDS => 'сек.',
            self::MINUTES => 'мин.',
            self::HOURS => 'час.',
            self::DAYS => 'дн.',
        };
    }
}
