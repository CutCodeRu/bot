<?php

declare(strict_types=1);

namespace App\Services;

use App\Bot\BotCore;
use App\Bot\Contracts\StorageContract;
use App\Bot\Enums\DelayType;
use App\Bot\MessageFactory;
use App\Jobs\SendMessageJob;
use App\Models\Message;
use App\Models\MessageHistory;
use App\Models\MessageSchedule;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use TelegramBot\Api\Types\InputMedia\ArrayOfInputMedia;
use TelegramBot\Api\Types\InputMedia\InputMediaPhoto;

final class BotMessageSender
{
    public function __construct(
        private readonly BotCore $core,
        private MessageFactory $message,
        private readonly StorageContract $storage,
    ) {
    }

    public function setMessage(MessageFactory $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function send(bool $saveHistory = true, bool $scheduleNext = true): void
    {
        if ($this->message->getAttachments()->isNotEmpty()) {
            $attachments = $this->message->getAttachments()->toArray();

            foreach ($attachments as $attachment) {
                $mime = $this->storage->mime($attachment);

                if (! str_contains($mime, 'image')) {
                    $this->cacheFilesIds([
                        $this->core->getApi()->sendDocument(
                            $this->message->getChatId(),
                            $this->getCachedFile($this->storage->url($attachment)),
                        ),
                    ], $attachment, true);

                    continue;
                }

                $media = new ArrayOfInputMedia();
                $media->addItem(
                    new InputMediaPhoto(
                        $this->getCachedFile($this->storage->url($attachment))
                    )
                );

                $this->cacheFilesIds(
                    $this->core->getApi()->sendMediaGroup(
                        $this->message->getChatId(),
                        $media
                    ),
                    $attachment
                );
            }
        }

        $keyboard = $this->message->getButtons()->isNotEmpty() ? new InlineKeyboardMarkup(
            [
                array_filter(
                    $this->message->getButtons()->toArray()
                ),
            ],
        ) : null;


        $this->core->getApi()->sendMessage(
            $this->message->getChatId(),
            $this->message->getMessage(),
            'MarkdownV2',
            replyMarkup: $keyboard
        );

        if ($saveHistory) {
            MessageHistory::query()->create([
                'bot_id' => $this->message->getBotId(),
                'user_id' => $this->message->getUser()->getId(),
                'payload' => $this->message->toArray(),
            ]);
        }

        if ($this->message->getBusId() === null) {
            return;
        }

        if ($this->message->isEvent()) {
            return;
        }

        if ($scheduleNext) {
            $this->scheduleNext();
        }
    }

    private function getCachedFile(string $path): string
    {
        return cache()->get(File::basename($path), $path);
    }

    /**
     * @param  list<\TelegramBot\Api\Types\Message>  $messages
     * @param  string  $attachment
     * @param  bool  $document
     *
     * @return void
     */
    private function cacheFilesIds(array $messages, string $attachment, bool $document = false): void
    {
        $ttl = now()->toImmutable()->addDay();

        foreach ($messages as $msg) {
            $file = $document ? $msg->getDocument() : Arr::last($msg->getPhoto());

            cache()->put(File::basename($attachment), $file->getFileId(), $ttl);
        }
    }

    private function scheduleNext(): void
    {
        $position = $this->message->getPosition() + 1;

        /** @var Message $nextMsg */
        $next = Message::query()
            ->default()
            ->active()
            ->where('message_bus_id', $this->message->getBusId())
            ->where('position', '>=', $position)
            ->orderBy('position')
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
            $next->attachments,
            $next->buttons,
        ))->withTags();

        if ($next->delay === 0) {
            $this->setMessage($nextMsg)->send();

            return;
        }

        if ($next->delay < 60 && $next->delay_type === DelayType::SECONDS) {
            SendMessageJob::dispatch($nextMsg)
                ->delay(now()->toImmutable()->addSeconds($next->delay));

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
