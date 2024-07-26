<?php

namespace App\Http\Controllers;

use App\Bot\Entities\TargetUser;
use App\Bot\MessageFactory;
use App\Jobs\SendMessageJob;
use App\Models\Bot;
use App\Models\Message as MessageModel;
use App\Models\MessageBus;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use TelegramBot\Api\Types\CallbackQuery;
use TelegramBot\Api\Types\Message;
use Throwable;

class WebhookController extends Controller
{
    public function __invoke(Bot $bot, Request $request)
    {
        try {
            $core = $bot->getBotCore();


            $core->getClient()->callbackQuery(function (CallbackQuery $q) use ($bot, $core) {
                $msg = MessageModel::query()
                    ->event()
                    ->find($q->getData());

                $user = $q->getFrom();

                $core->getApi()->editMessageReplyMarkup(
                    $q->getMessage()->getChat()->getId(),
                    $q->getMessage()->getMessageId(),
                    null
                );

                $this->sendMessage(
                    $bot,
                    $msg,
                    new TargetUser($user->getId(), $user->getFirstName(), $user->getLastName(), $user->getUsername()),
                    $q->getMessage()->getChat()->getId()
                );
            });

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

                if ($user !== null && ! isset($user->chats[$bot->getKey()])) {
                    $user->chats = collect($user->chats)
                        ->put($bot->getKey(), $message->getChat()->getId())
                        ->toArray();
                    $user->save();
                }

                if ($user === null) {
                    $user = User::query()->create([
                        'id' => $from->getId(),
                        'first_name' => $from->getFirstName() ?? '',
                        'last_name' => $from->getLastName() ?? '',
                        'username' => $from->getUsername() ?? '',
                        'chats' => [
                            $bot->getKey() => $message->getChat()->getId(),
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

                $this->sendMessage(
                    $bot,
                    $msg,
                    new TargetUser($user->getKey(), $user->first_name, $user->last_name, $user->username),
                    $message->getChat()->getId()
                );
            });

            $core->getClient()->run();
        } catch (Throwable $e) {
            logger($e->getMessage());
        }
    }

    private function sendMessage(Bot $bot, MessageModel $msg, TargetUser $user, int $chatId): void
    {
        $msgFactory = (new MessageFactory(
            $chatId,
            $bot->getKey(),
            $msg->message_bus_id,
            $msg->position,
            $user,
            $msg->message,
            $msg->attachments ?? collect(),
            $msg->buttons ?? collect(),
        ))->event($msg->isEvent())->withTags();

        SendMessageJob::dispatch($msgFactory);
    }
}
