<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use App\Bot\Enums\DelayType;
use App\Models\Message;
use Illuminate\Database\Eloquent\Model;
use MoonShine\Components\FlexibleRender;
use MoonShine\Components\MoonShineComponent;
use MoonShine\Decorations\Block;
use MoonShine\Decorations\Column;
use MoonShine\Decorations\Grid;
use MoonShine\Fields\Enum;
use MoonShine\Fields\Field;
use MoonShine\Fields\File;
use MoonShine\Fields\ID;
use MoonShine\Fields\Json;
use MoonShine\Fields\Markdown;
use MoonShine\Fields\Number;
use MoonShine\Fields\Relationships\BelongsTo;
use MoonShine\Fields\Switcher;
use MoonShine\Fields\Td;
use MoonShine\Fields\Text;
use MoonShine\Fields\Url;
use MoonShine\Handlers\ExportHandler;
use MoonShine\Handlers\ImportHandler;
use MoonShine\Resources\ModelResource;

/**
 * @extends ModelResource<Message>
 */
class MessageResource extends ModelResource
{
    protected string $model = Message::class;

    protected string $title = 'Сообщение';

    /**
     * @return list<MoonShineComponent|Field>
     */
    public function fields(): array
    {
        return [
            Block::make([
                ID::make()->sortable(),

                Number::make('Позиция', 'position')
                    ->default(1)
                    ->step(1)
                    ->min(1)
                    ->buttons(),

                BelongsTo::make('Цепочка', 'messageBus', resource: new MessageBusResource())
                    ->hideOnIndex(),
                Text::make('Заголовок', 'title')
                    ->hint('Не используется при отправке')
                    ->required(),

                Markdown::make('Сообщение', 'message')
                    ->hideOnIndex()
                    ->hint('Доступные теги: {id},{first_name},{last_name},{username}'),

                Td::make('Задержка', function (Message $message) {
                    return [
                        FlexibleRender::make(sprintf('%d (%s)', $message->delay, $message->delay_type->toString())),
                    ];
                }),

                Grid::make([
                    Column::make([
                        Number::make('Таймер', 'delay')
                            ->hideOnIndex()
                            ->hideOnDetail()
                            ->default(0)
                            ->hint('Время до отправки сообщения после предыдущего, если 0 то будет отправлено сразу')
                            ->buttons(),
                    ])->columnSpan(9),
                    Column::make([
                        Enum::make('Ед. таймера', 'delay_type')
                            ->hideOnIndex()
                            ->hideOnDetail()
                            ->attach(DelayType::class),
                    ])->columnSpan(3),
                ]),

                Text::make('Точное время отправки', 'delay_at')
                    ->showWhen('delay_type', DelayType::DAYS->value)
                    ->hideOnIndex()
                    ->hideOnDetail()
                    ->nullable()
                    ->mask('99:99'),

                Json::make('Кнопки', 'buttons')
                    ->fields([
                        Text::make('Заголовок', 'text'),
                        Url::make('Url', 'url'),
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
