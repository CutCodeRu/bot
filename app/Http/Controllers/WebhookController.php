<?php

namespace App\Http\Controllers;

use App\Bot\Entities\TargetUser;
use App\Bot\MessageFactory;
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

                $spamMode = app()->isLocal() || config('bot.spam_mode', false);

                $lock = Cache::lock(
                    "webhook_{$bot->getKey()}_{$from->getId()}",
                    $spamMode ? 1 : 120
                );

                if (! $lock->get()) {
                    return;
                }

                $user = User::query()->find($from->getId());

                if($user !== null && !isset($user->chats[$bot->getKey()])) {
                    $user->chats = collect($user->chats)
                        ->put($bot->getKey(), $message->getChat()->getId())
                        ->toArray();
                    $user->save();
                }

                if($user === null) {
                    $user = User::query()->create([
                        'id' => $from->getId(),
                        'first_name' => $from->getFirstName() ?? '',
                        'last_name' => $from->getLastName() ?? '',
                        'username' => $from->getUsername() ?? '',
                        'chats' => [
                            $bot->getKey() => $message->getChat()->getId()
                        ],
                    ]);
                }

                $bus = MessageBus::query()
                    ->whereBelongsTo($bot)
                    ->active()
                    ->firstOrFail();

                $msg = $bus->getFirstMessage();

                if ($msg === null) {
                    return;
                }

                $msgFactory = (new MessageFactory(
                    $message->getChat()->getId(),
                    $bot->getKey(),
                    $bus->getKey(),
                    $msg->position,
                    new TargetUser($user->getKey(), $user->first_name, $user->last_name, $user->username),
                    $msg->message,
                    $msg->attachements ?? collect(),
                    $msg->buttons ?? collect(),
                ))->withTags();

                SendMessageJob::dispatch($msgFactory);
            });

            $core->getClient()->run();
        } catch (Throwable $e) {
            logger($e->getMessage());
        }
    }
}
