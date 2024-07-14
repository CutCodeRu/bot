<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'first_name',
        'last_name',
        'username',
        'chats',
        'is_active',
    ];


    protected function casts(): array
    {
        return [
            'chats' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function messageHistories(): HasMany
    {
        return $this->hasMany(MessageHistory::class)->latest();
    }

    public function messageSchedules(): HasMany
    {
        return $this->hasMany(MessageSchedule::class)->latest();
    }
}
