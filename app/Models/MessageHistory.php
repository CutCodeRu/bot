<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageHistory extends Model
{
    protected $fillable = [
        'bot_id',
        'user_id',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'collection',
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
