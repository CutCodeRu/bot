<?php

namespace App\Models;

use App\Bot\Enums\DispatchStatus;
use App\Traits\MessageDispatchNow;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Newsletter extends Model
{
    use MessageDispatchNow;

    protected $fillable = [
        'bot_id',
        'title',
        'sent_at',
        'message',
        'attachments',
        'buttons',
        'is_active',
        'status',
        'errors',
    ];

    protected function casts(): array
    {
        return [
            'attachments' => 'collection',
            'buttons' => 'collection',
            'sent_at' => 'datetime',
            'is_active' => 'bool',
            'status' => DispatchStatus::class,
        ];
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    public function newsletterUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
