<?php

declare(strict_types=1);

namespace App\Bot\Contracts;

interface StorageContract
{
    public function url(string $path): string;

    public function path(string $path): string;

    public function mime(string $path): string;
}
