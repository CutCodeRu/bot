<?php

namespace App\Http\Controllers;

use App\Jobs\SendMessageJob;
use App\Models\Bot;
use App\Models\MessageBus;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use TelegramBot\Api\Types\Message;
use Throwable;

class WebhookController extends Controller
{
    public function __invoke(Bot $bot, Request $request)
    {
        try {
            $core = $bot->getBotCore();

            $core->getClient()->command('start', function (Message $message) use ($bot) {
                $from = $message->getFrom();

                if ($from === null) {
                    return;
                }

                $lock = Cache::lock("webhook_{$bot->getKey()}_{$from->getId()}", app()->isLocal() ? 5 : 120);

                if (! $lock->get()) {
                    return;
                }

                $user = User::query()->find($from->getId());

                if ($user !== null) {
                    return;
                }

                $user = User::query()->create([
                    'id' => $from->getId(),
                    'first_name' => $from->getFirstName(),
                    'last_name' => $from->getLastName(),
                    'username' => $from->getUsername(),
                    'chats' => [
                        $bot->getKey() => $message->getChat()->getId()
                    ],
                ]);

                $bus = MessageBus::query()
                    ->whereBelongsTo($bot)
                    ->active()
                    ->firstOrFail();

                $msg = $bus->getFirstMessage();

                if ($msg === null) {
                    return;
                }

                SendMessageJob::dispatch(
                    chatId: $message->getChat()->getId(),
                    bot: $bot,
                    user: $user,
                    message: $msg
                );
            });

            $core->getClient()->run();
        } catch (Throwable $e) {
            logger($e->getMessage());
        }
    }
}
