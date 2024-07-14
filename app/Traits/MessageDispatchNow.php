<?php

declare(strict_types=1);

namespace App\Traits;

use App\Bot\Enums\DispatchStatus;
use Illuminate\Contracts\Database\Eloquent\Builder;

trait MessageDispatchNow
{
    public function scopeDispatchNow(Builder $query, bool $withSeconds = false): void
    {
        $query->where('status', DispatchStatus::QUEUED);

        if($withSeconds) {
            $query->where('sent_at', now());
        } else {
            $query->whereRaw("DATE_FORMAT(sent_at, '%Y-%m-%d %H:%i') = ?", [now()->format('Y-m-d H:i')]);
        }
    }
}
