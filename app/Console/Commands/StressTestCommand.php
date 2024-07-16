<?php

namespace App\Console\Commands;

use App\Bot\Entities\TargetUser;
use App\Bot\MessageFactory;
use App\Jobs\SendMessageJob;
use Illuminate\Console\Command;

use function Laravel\Prompts\text;

class StressTestCommand extends Command
{
    protected $signature = 'app:stress-test {chat?} {bot?} {user?}';

    public function handle(): void
    {
        $chatId = $this->argument('chat') ?? text('Chat id', required: true);
        $botId = $this->argument('bot') ?? text('Bot id', required: true);
        $userId = $this->argument('user') ?? text('User id', required: true);

        for ($i = 1; $i < 40; $i++) {
            SendMessageJob::dispatch(
                new MessageFactory(
                    $chatId,
                    $botId,
                    null,
                    null,
                    new TargetUser($userId),
                    "Stress message $i",
                )
            );
        }
    }
}
