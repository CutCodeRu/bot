<?php

declare(strict_types=1);

namespace App\Bot;

use TelegramBot\Api\BotApi;
use TelegramBot\Api\Client;

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
        retry(3, fn () => $this->getApi()->setWebhook($url), 2000);
    }
}
