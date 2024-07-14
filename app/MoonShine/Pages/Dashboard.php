<?php

declare(strict_types=1);

namespace App\MoonShine\Pages;

use App\Bot\Enums\DispatchStatus;
use App\Models\MessageHistory;
use App\Models\MessageSchedule;
use App\Models\Newsletter;
use MoonShine\Decorations\Grid;
use MoonShine\Metrics\ValueMetric;
use MoonShine\Pages\Page;
use MoonShine\Components\MoonShineComponent;

class Dashboard extends Page
{
    /**
     * @return array<string, string>
     */
    public function breadcrumbs(): array
    {
        return [
            '#' => $this->title()
        ];
    }

    public function title(): string
    {
        return $this->title ?: 'Dashboard';
    }

    /**
     * @return list<MoonShineComponent>
     */
    public function components(): array
	{
		return [
            Grid::make([
                ValueMetric::make('Запланированных сообщений')
                    ->value(MessageSchedule::query()->where('status', DispatchStatus::QUEUED)->count())
                    ->columnSpan(6),

                ValueMetric::make('Запланированных рассылок')
                    ->value(Newsletter::query()->where('status', DispatchStatus::QUEUED)->count())
                    ->columnSpan(6),


                ValueMetric::make('Отправлено сегодня')
                    ->value(MessageHistory::query()->whereDate('created_at', now())->count())
                    ->columnSpan(12),
            ])
        ];
	}
}
