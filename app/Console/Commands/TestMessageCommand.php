<?php

namespace App\Console\Commands;

use App\Bot\BotCore;
use App\Bot\Entities\TargetUser;
use App\Bot\MessageFactory;
use App\Jobs\SendMessageJob;
use App\Models\Bot;
use App\Services\BotMessageSender;
use App\Services\BotStorage;
use Illuminate\Console\Command;

use function Laravel\Prompts\text;

class TestMessageCommand extends Command
{
    protected $signature = 'app:test-message {chat?} {bot?} {user?}';

    public function handle(): void
    {
        $chatId = $this->argument('chat') ?? text('Chat id', required: true);
        $botId = $this->argument('bot') ?? text('Bot id', required: true);
        $userId = $this->argument('user') ?? text('User id', required: true);

        $message = text('Message', required: true);
        $queue = $this->confirm('Queue?');

        $msg = new MessageFactory(
            $chatId,
            $botId,
            null,
            null,
            new TargetUser($userId),
            $message,
            attachments: collect([
                'attachments/rcJE1zOgT8t6pTdJGrAkhyYBWfgMvLSC1b8GccGS.jpg',
                'attachments/8TcsR0oWxoTHtuYtvoUzLvOD7Z0rO6eVkDeuDald.pdf',
            ]),
            buttons: collect([
                ['text' => 'Go', 'url' => 'https://cutcode.dev'],
            ])
        );

        if($queue) {
            SendMessageJob::dispatch($msg);

            return;
        }

        $bot = Bot::find($botId);
        $core = new BotCore($bot->token);
        $sender = new BotMessageSender($core, $msg, new BotStorage());

        $sender->send(saveHistory: false, scheduleNext: false);
    }
}
