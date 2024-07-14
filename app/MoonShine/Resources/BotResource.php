<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use Illuminate\Database\Eloquent\Model;
use App\Models\Bot;

use MoonShine\Fields\Password;
use MoonShine\Fields\Preview;
use MoonShine\Fields\Text;
use MoonShine\Fields\Url;
use MoonShine\Handlers\ExportHandler;
use MoonShine\Handlers\ImportHandler;
use MoonShine\Resources\ModelResource;
use MoonShine\Decorations\Block;
use MoonShine\Fields\ID;
use MoonShine\Fields\Field;
use MoonShine\Components\MoonShineComponent;

/**
 * @extends ModelResource<Bot>
 */
class BotResource extends ModelResource
{
    protected string $model = Bot::class;

    protected string $title = 'Боты';

    protected string $column = 'name';

    /**
     * @return list<MoonShineComponent|Field>
     */
    public function fields(): array
    {
        return [
            Block::make([
                ID::make()->sortable(),

                Text::make('Name')->required(),

                Text::make('Token')
                    ->required()
                    ->hideOnIndex()
                    ->hideOnDetail()
                    ->eye()
                    ->locked(),

                Preview::make('Webhook')
                    ->link(static fn($v) => $v, static fn($v) => $v)
                    ->hideOnForm(),
            ]),
        ];
    }

    /**
     * @param Bot $item
     *
     * @return array<string, string[]|string>
     * @see https://laravel.com/docs/validation#available-validation-rules
     */
    public function rules(Model $item): array
    {
        return [
            'name' => ['required', 'string'],
            'token' => ['required', 'string'],
        ];
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
