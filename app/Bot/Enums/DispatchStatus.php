<?php

declare(strict_types=1);

namespace App\Bot\Enums;

enum DispatchStatus: int
{
    case QUEUED = 0;

    case SENT = 1;

    case FAILED = 3;

    public function toString(): string
    {
        return match ($this) {
            self::QUEUED => 'Запланирован',
            self::SENT => 'Отправлен',
            self::FAILED => 'Ошибка',
        };
    }
}
