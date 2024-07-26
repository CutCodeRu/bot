<?php

declare(strict_types=1);

namespace App\Bot\Enums;

enum MessageType: int
{
    case DEFAULT = 0;

    case EVENT = 1;

    public function toString(): string
    {
        return match ($this) {
            self::DEFAULT => 'Цепочка',
            self::EVENT => 'Событие',
        };
    }
}
