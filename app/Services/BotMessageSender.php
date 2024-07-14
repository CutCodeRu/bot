<?php

declare(strict_types=1);

namespace App\Services;

use App\Bot\BotCore;
use App\Bot\Contracts\StorageContract;
use App\Bot\MessageFactory;
use App\Models\Message;
use App\Models\MessageHistory;
use App\Models\MessageSchedule;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use TelegramBot\Api\Types\InputMedia\ArrayOfInputMedia;
use TelegramBot\Api\Types\InputMedia\InputMediaPhoto;

final class BotMessageSender
{
    public function __construct(
        private readonly BotCore $core,
        private MessageFactory $message,
        private readonly StorageContract $storage,
    )
    {
    }

    public function setMessage(MessageFactory $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function send(): void
    {
        if($this->message->getAttachments()->isNotEmpty()) {
            $media = new ArrayOfInputMedia();

            foreach ($this->message->getAttachments() as $attachment) {
                if(!str_contains($this->storage->mime($attachment), 'image')) {
                    continue;
                }

                $media->addItem(new InputMediaPhoto($this->storage->url($attachment)));
            }

            if($media->count()) {
                $this->core->getApi()->sendMediaGroup($this->message->getChatId(), $media);
            }
        }

        $keyboard = $this->message->getButtons()->isNotEmpty() ? new InlineKeyboardMarkup(
            [
                $this->message->getButtons()->toArray()
            ]
        ) : null;

        $this->core->getApi()->sendMessage(
            $this->message->getChatId(),
            $this->message->getMessage(),
            'MarkdownV2',
            replyMarkup: $keyboard
        );

        MessageHistory::query()->create([
            'bot_id' => $this->message->getBotId(),
            'user_id' => $this->message->getUser()->getId(),
            'payload' => $this->message->toArray()
        ]);

        if($this->message->getBusId() === null) {
            return;
        }

        $this->scheduleNext();
    }

    private function scheduleNext(): void
    {
        $position = $this->message->getPosition() + 1;

        /** @var Message $nextMsg */
        $next = Message::query()
            ->active()
            ->where('message_bus_id', $this->message->getBusId())
            ->where('position', $position)
            ->first();

        if ($next === null) {
            return;
        }

        $nextMsg = (new MessageFactory(
            $this->message->getChatId(),
            $this->message->getBotId(),
            $this->message->getBusId(),
            $position,
            $this->message->getUser(),
            $next->message,
            $next->attachements,
            $next->buttons,
        ))->withTags();

        if ($next->delay === 0) {
            $this->setMessage($nextMsg)->send();

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