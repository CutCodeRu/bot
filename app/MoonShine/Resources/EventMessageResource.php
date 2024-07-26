<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use App\Bot\Enums\DelayType;
use App\Bot\Enums\MessageType;
use App\Models\Message;
use Illuminate\Database\Eloquent\Model;
use MoonShine\Components\FlexibleRender;
use MoonShine\Decorations\Block;
use MoonShine\Decorations\Column;
use MoonShine\Decorations\Grid;
use MoonShine\Fields\Enum;
use MoonShine\Fields\Field;
use MoonShine\Fields\File;
use MoonShine\Fields\Hidden;
use MoonShine\Fields\ID;
use MoonShine\Fields\Json;
use MoonShine\Fields\Markdown;
use MoonShine\Fields\Number;
use MoonShine\Fields\Relationships\BelongsTo;
use MoonShine\Fields\Switcher;
use MoonShine\Fields\Td;
use MoonShine\Fields\Text;
use MoonShine\Handlers\ExportHandler;
use MoonShine\Handlers\ImportHandler;
use MoonShine\Resources\ModelResource;

/**
 * @extends ModelResource<Message>
 */
class EventMessageResource extends ModelResource
{
    protected string $model = Message::class;

    protected string $title = 'События';

    public function getActiveActions(): array
    {
        return ['view', 'create', 'update', 'delete'];
    }

    /**
     * @return Field
     */
    public function fields(): array
    {
        return [
            Block::make([
                ID::make()->sortable(),

                BelongsTo::make('Цепочка', 'messageBus', resource: new MessageBusResource())
                    ->hideOnIndex(),

                Text::make('Заголовок', 'title')
                    ->hint('Не используется при отправке')
                    ->required(),

                Markdown::make('Сообщение', 'message')
                    ->hideOnIndex()
                    ->hint('Доступные теги: {id},{first_name},{last_name},{username}'),

                Json::make('Кнопки', 'buttons')
                    ->fields([
                        Text::make('Заголовок', 'text'),
                        Text::make('Url', 'url'),
                        Text::make('ID события (опционально)', 'callback_data'),
                    ])
                    ->hideOnIndex(),

                File::make('Файлы', 'attachments')
                    ->hideOnIndex()
                    ->disk('public')
                    ->dir('attachments')
                    ->multiple()
                    ->removable(),

                Switcher::make('Активно', 'is_active')->default(true),
            ]),
        ];
    }

    protected function beforeCreating(Model $item): Model
    {
        $item->type = MessageType::EVENT;

        return $item;
    }

    /**
     * @param  Message  $item
     *
     * @return array<string, string[]|string>
     * @see https://laravel.com/docs/validation#available-validation-rules
     */
    public function rules(Model $item): array
    {
        return [
            'title' => ['required', 'string'],
            'message' => ['required', 'string'],
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
