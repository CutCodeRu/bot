<?php

namespace App\Console\Commands;

use App\Bot\BotCore;
use App\Bot\Entities\TargetUser;
use App\Bot\MessageFactory;
use App\Jobs\SendMessageJob;
use App\Models\Bot;
use App\Models\Message;
use App\Services\BotMessageSender;
use App\Services\BotStorage;
use Illuminate\Console\Command;

use function Laravel\Prompts\text;

class TestMessageCommand extends Command
{
    protected $signature = 'app:test-message {chat?} {bot?} {user?} {message?}';

    public function handle(): void
    {
        $chatId = $this->argument('chat') ?? text('Chat id', required: true);
        $botId = $this->argument('bot') ?? text('Bot id', required: true);
        $userId = $this->argument('user') ?? text('User id', required: true);
        $messageId = $this->argument('message') ?? false;

        $buttons = [
            ['text' => 'Go', 'url' => 'https://cutcode.dev'],
        ];

        $attachments = [
            'attachments/rcJE1zOgT8t6pTdJGrAkhyYBWfgMvLSC1b8GccGS.jpg',
            'attachments/8TcsR0oWxoTHtuYtvoUzLvOD7Z0rO6eVkDeuDald.pdf',
        ];

        if($messageId) {
            $model = Message::query()->findOrFail($messageId);
            $message = $model->message;
            $buttons = $model->buttons;
            $attachments = $model->attachments;
        } else {
            $message = text('Message', required: true);
        }

        $queue = config('queue.default') !== 'sync' && $this->confirm('Queue?');

        $msg = new MessageFactory(
            $chatId,
            $botId,
            null,
            null,
            new TargetUser($userId),
            $message,
            attachments: collect($attachments),
            buttons: collect($buttons)
        );

        if($queue) {
            SendMessageJob::dispatch($msg);

            return;
        }

        $bot = Bot::find($botId);

        $bot->webhook = app()->isLocal()
            ? config('app.url') . route('webhook', $bot, false)
            : route('webhook', $bot);

        (new BotCore($bot->token))->setWebhook($bot->webhook);

        $core = new BotCore($bot->token);
        $sender = new BotMessageSender($core, $msg, new BotStorage());

        $sender->send(saveHistory: false, scheduleNext: false);
    }
}
