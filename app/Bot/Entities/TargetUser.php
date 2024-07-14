<?php

declare(strict_types=1);

namespace App\Bot\Entities;

final readonly class TargetUser
{
    public function __construct(
        private string $id,
        private string $first_name,
        private string $last_name,
        private ?string $username = null,
    )
    {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getFirstName(): string
    {
        return $this->first_name;
    }

    public function getLastName(): string
    {
        return $this->last_name;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public static function fromArray(array $data): self
    {
        return new self(...$data);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'username' => $this->username,
        ];
    }
}
