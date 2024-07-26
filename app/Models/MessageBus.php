<?php

namespace App\Models;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MessageBus extends Model
{
    protected $fillable = [
        'bot_id',
        'title',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function getFirstMessage(): ?Message
    {
        return $this->messages()->active()->first();
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)
            ->default()
            ->orderBy('position');
    }

    public function eventMessages(): HasMany
    {
        return $this->hasMany(Message::class)
            ->event()
            ->oldest();
    }
}
