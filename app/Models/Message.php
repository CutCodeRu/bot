<?php

namespace App\Models;

use App\Bot\Enums\DelayType;
use App\Bot\Enums\MessageType;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $fillable = [
        'message_bus_id',
        'type',
        'title',
        'message',
        'delay',
        'delay_type',
        'delay_at',
        'attachments',
        'buttons',
        'is_active',
        'position',
    ];

    protected static function booted(): void
    {
        static::creating(static function (self $model) {
            if($model->type === MessageType::DEFAULT) {
                $model->position = $model->messageBus
                    ->messages()
                    ->default()
                    ->active()
                    ->count() + 1;
            } else {
                $model->position = 0;
                $model->delay_type = 0;
                $model->delay_at = null;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'bool',
            'attachments' => 'collection',
            'buttons' => 'collection',
            'delay_type' => DelayType::class,
            'type' => MessageType::class,
        ];
    }

    public function isEvent(): bool
    {
        return $this->type === MessageType::EVENT;
    }

    public function scopeDefault(Builder $query): void
    {
        $query->where('type', MessageType::DEFAULT);
    }

    public function scopeEvent(Builder $query): void
    {
        $query->where('type', MessageType::EVENT);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function getDelay(): CarbonImmutable
    {
        $date = now()->toImmutable()->add($this->delay_type->name, $this->delay);

        if ($this->delay_type === DelayType::DAYS && ! empty($this->delay_at)) {
            [$hour, $minute] = explode(':', $this->delay_at);

            $date = $date->setTime($hour, $minute);
        }

        return $date;
    }

    public function messageBus(): BelongsTo
    {
        return $this->belongsTo(MessageBus::class);
    }
}
