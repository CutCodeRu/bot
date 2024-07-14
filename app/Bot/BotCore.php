<?php

declare(strict_types=1);

namespace App\Bot;

use App\Bot\Contracts\StorageContract;
use App\Models\Message;
use App\Models\MessageHistory;
use App\Models\MessageSchedule;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Client;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use TelegramBot\Api\Types\InputMedia\ArrayOfInputMedia;
use TelegramBot\Api\Types\InputMedia\InputMediaPhoto;

final class BotCore
{
    private readonly BotApi $api;
    private readonly Client $client;

    public function __construct(string $token)
    {
        $this->api = new BotApi($token);
        $this->client = new Client($token);
    }

    public function getApi(): BotApi
    {
        return $this->api;
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function setWebhook(string $url): void
    {
        retry(3, fn() => $this->getApi()->setWebhook($url), 2000);
    }

    public function send(MessageFactory $msg, StorageContract $storage): void
    {
        if($msg->getAttachments()->isNotEmpty()) {
            $media = new ArrayOfInputMedia();

            foreach ($msg->getAttachments() as $attachment) {
                if(!str_contains($storage->mime($attachment), 'image')) {
                    continue;
                }

                $media->addItem(new InputMediaPhoto($storage->url($attachment)));
            }

            if($media->count()) {
                $this->getApi()->sendMediaGroup($msg->getChatId(), $media);
            }
        }

        $keyboard = $msg->getButtons()->isNotEmpty() ? new InlineKeyboardMarkup(
            [
                $msg->getButtons()->toArray()
            ]
        ) : null;

        $this->getApi()->sendMessage(
            $msg->getChatId(),
            $msg->getMessage(),
            'MarkdownV2',
            replyMarkup: $keyboard
        );

        MessageHistory::query()->create([
            'bot_id' => $msg->getBotId(),
            'user_id' => $msg->getUser()->getId(),
            'payload' => $msg->toArray()
        ]);

        if($msg->getBusId() === null) {
            return;
        }

        $this->scheduleNext($msg, $storage);
    }

    private function scheduleNext(MessageFactory $prev, StorageContract $storage): void
    {
        $position = $prev->getPosition() + 1;

        /** @var Message $nextMsg */
        $next = Message::query()
            ->active()
            ->where('message_bus_id', $prev->getBusId())
            ->where('position', $position)
            ->first();

        if ($next === null) {
            return;
        }

        $nextMsg = (new MessageFactory(
            $prev->getChatId(),
            $prev->getBotId(),
            $prev->getBusId(),
            $position,
            $prev->getUser(),
            $next->message,
            $next->attachements,
            $next->buttons,
        ))->withTags();

        if ($next->delay === 0) {
            $this->send($nextMsg, $storage);

            return;
        }

        MessageSchedule::query()->create([
            'bot_id' => $nextMsg->getBotId(),
            'user_id' => $nextMsg->getUser()->getId(),
            'sent_at' => $next->getDelay(),
            'payload' => $nextMsg->toArray(),
        ]);
    }
}
