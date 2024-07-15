<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use Illuminate\Database\Eloquent\Model;
use App\Models\MessageBus;

use Illuminate\Validation\ValidationException;
use MoonShine\Fields\Relationships\BelongsTo;
use MoonShine\Fields\Relationships\HasMany;
use MoonShine\Fields\Switcher;
use MoonShine\Fields\Text;
use MoonShine\Handlers\ExportHandler;
use MoonShine\Handlers\ImportHandler;
use MoonShine\Resources\ModelResource;
use MoonShine\Decorations\Block;
use MoonShine\Fields\ID;
use MoonShine\Fields\Field;
use MoonShine\Components\MoonShineComponent;

/**
 * @extends ModelResource<MessageBus>
 */
class MessageBusResource extends ModelResource
{
    protected string $model = MessageBus::class;

    protected string $title = 'Цепочки сообщений';

    protected string $column = 'title';

    /**
     * @return list<MoonShineComponent|Field>
     */
    public function fields(): array
    {
        return [
            Block::make([
                ID::make()->sortable(),
                BelongsTo::make('Бот', 'bot')->required(),
                Text::make('Заголовок', 'title')->required(),
                Switcher::make('Активен', 'is_active')->default(true),

                HasMany::make('Сообщения', 'messages')
                    ->async()
                    ->creatable()
            ]),
        ];
    }

    public function prepareForValidation(): void
    {
        if(is_null($this->getItemID()) && MessageBus::query()->where('bot_id', request()->integer('bot_id'))->exists()) {
            throw ValidationException::withMessages([
                'bot_id' => 'У данного бота уже есть цепочка сообщений',
            ]);
        }
    }

    /**
     * @param MessageBus $item
     *
     * @return array<string, string[]|string>
     * @see https://laravel.com/docs/validation#available-validation-rules
     */
    public function rules(Model $item): array
    {
        return [
            'title' => ['required', 'string']
        ];
    }

    public function filters(): array
    {
        return [
            Switcher::make('Активен', 'is_active'),
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
