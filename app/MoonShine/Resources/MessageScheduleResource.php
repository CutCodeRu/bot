<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use App\Models\MessageSchedule;
use Illuminate\Database\Eloquent\Model;
use MoonShine\Components\MoonShineComponent;
use MoonShine\Decorations\Block;
use MoonShine\Fields\Field;
use MoonShine\Fields\ID;
use MoonShine\Handlers\ExportHandler;
use MoonShine\Handlers\ImportHandler;
use MoonShine\Resources\ModelResource;

/**
 * @extends ModelResource<MessageSchedule>
 */
class MessageScheduleResource extends ModelResource
{
    protected string $model = MessageSchedule::class;

    protected string $title = 'Рассылки';

    /**
     * @return list<MoonShineComponent|Field>
     */
    public function fields(): array
    {
        return [
            Block::make([
                ID::make()->sortable(),
            ]),
        ];
    }

    /**
     * @param  MessageSchedule  $item
     *
     * @return array<string, string[]|string>
     * @see https://laravel.com/docs/validation#available-validation-rules
     */
    public function rules(Model $item): array
    {
        return [];
    }

    public function export(): ?ExportHandler
    {
        return null;
    }

    public function import(): ?ImportHandler
    {
        return null;
    }
}
