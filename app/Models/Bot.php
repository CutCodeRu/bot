<?php

namespace App\Models;

use App\Bot\BotCore;
use App\Bot\Commands\StartCommand;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bot extends Model
{
    protected $fillable = [
        'name',
        'token',
        'webhook'
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::created(static function (self $bot) {
            $bot->webhook = app()->isLocal()
                ? config('app.url') . route('webhook', $bot, false)
                : route('webhook', $bot);

            (new BotCore($bot->token))->setWebhook($bot->webhook);

            $bot->save();
        });
    }

    public function getBotCore(): BotCore
    {
        return (new BotCore($this->token));
    }

    public function messageBuses(): HasMany
    {
        return $this->hasMany(MessageBus::class);
    }

    public function newsletters(): HasMany
    {
        return $this->hasMany(Newsletter::class);
    }

    public function messageHistories(): HasMany
    {
        return $this->hasMany(MessageHistory::class);
    }

    public function messageSchedules(): HasMany
    {
        return $this->hasMany(MessageSchedule::class);
    }
}
