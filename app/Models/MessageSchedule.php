<?php

namespace App\Models;

use App\Bot\Enums\DispatchStatus;
use App\Traits\MessageDispatchNow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageSchedule extends Model
{
    use MessageDispatchNow;

    protected $fillable = [
        'sent_at',
        'payload',
        'bot_id',
        'user_id',
        'status',
        'errors',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'sent_at' => 'datetime',
            'status' => DispatchStatus::class,
        ];
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
