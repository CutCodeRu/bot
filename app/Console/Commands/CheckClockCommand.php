<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckClockCommand extends Command
{
    protected $signature = 'app:check-clock';

    public function handle(): void
    {
        $this->info(now()->format('Y-m-d H:i:s'));
    }
}
