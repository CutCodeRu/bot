<?php

declare(strict_types=1);

namespace App\Bot;

use App\Bot\Entities\TargetUser;
use Illuminate\Support\Collection;

final class MessageFactory
{
    public function __construct(
        private readonly int $chatId,
        private readonly int $botId,
        private readonly ?int $busId,
        private readonly ?int $position,
        private readonly TargetUser $user,
        private string $message,
        private ?Collection $attachments = null,
        private ?Collection $buttons = null,
        private bool $event = false,
    )
    {
    }

    public function getRawMessage(): string
    {
        return $this->message ?? '';
    }

    public function getMessage(): string
    {
        $escaped = ['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'];

        $message = $this->message ?? '';

        $pattern = '/(\[.*?]\(.*?\))/';

        $escapedMessage = preg_replace_callback(
            $pattern,
            static fn ($matches) => 'PLACEHOLDER' . base64_encode($matches[0]) . 'PLACEHOLDER',
            $message
        );

        $escapedMessage = str($escapedMessage)
            ->replace($escaped, collect($escaped)->map(fn($char) => "\\$char")->toArray())
            ->value();

        return preg_replace_callback(
            '/PLACEHOLDER(.*?)PLACEHOLDER/',
            static fn ($matches) => base64_decode($matches[1]),
            $escapedMessage
        );
    }

    public function getAttachments(): Collection
    {
        return $this->attachments ?? collect();
    }

    public function getButtons(): Collection
    {
        return $this->buttons ?? collect();
    }

    public function getChatId(): int
    {
        return $this->chatId;
    }

    public function getBotId(): int
    {
        return $this->botId;
    }

    public function getUser(): TargetUser
    {
        return $this->user;
    }

    public function getBusId(): ?int
    {
        return $this->busId;
    }

    public function getPosition(): int
    {
        return $this->position ?? 1;
    }

    public function isEvent(): bool
    {
        return $this->event;
    }

    private function parseTags(string $message): string
    {
        return str_replace(
            ['{id}', '{first_name}', '{last_name}', '{username}'],
            [$this->user->getId(), $this->user->getUsername(), $this->user->getLastName(), $this->user->getFirstName()],
            $message
        );
    }

    public function withTags(): self
    {
        $this->message = $this->parseTags($this->message);

        $this->buttons = $this->getButtons()->map(
            fn (array $button): array => collect($button)
                ->mapWithKeys(fn (string $v, string $k): array => [$k => $this->parseTags($v)])
                ->toArray()
        );

        return $this;
    }

    public function event(bool $condition = true): self
    {
        $this->event = $condition;

        return $this;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['chat_id'],
            $data['bot_id'],
            $data['bus_id'],
            $data['position'],
            TargetUser::fromArray($data['user']),
            $data['message'],
            collect($data['attachments']),
            collect($data['buttons']),
            $data['event']
        );
    }

    public function toArray(): array
    {
        return [
            'chat_id' => $this->getChatId(),
            'bot_id' => $this->getBotId(),
            'bus_id' => $this->getBusId(),
            'position' => $this->getPosition(),
            'user' => $this->getUser()->toArray(),
            'message' => $this->getRawMessage(),
            'attachments' => $this->getAttachments()->toArray(),
            'buttons' => $this->getButtons()->toArray(),
            'event' => $this->isEvent(),
        ];
    }
}
