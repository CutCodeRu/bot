<?php

declare(strict_types=1);

namespace App\Services;

use App\Bot\Contracts\StorageContract;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

final class BotStorage implements StorageContract
{
    private readonly Filesystem $filesystem;

    public function __construct()
    {
        $this->filesystem = Storage::disk('public');
    }

    public function url(string $path): string
    {
        return $this->filesystem->url($path);
    }

    public function path(string $path): string
    {
        return $this->filesystem->path($path);
    }

    public function mime(string $path): string
    {
        return File::mimeType($this->path($path));
    }
}
