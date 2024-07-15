<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use App\Bot\Enums\DispatchStatus;
use Illuminate\Database\Eloquent\Model;
use App\Models\Newsletter;

use MoonShine\Fields\Date;
use MoonShine\Fields\Enum;
use MoonShine\Fields\File;
use MoonShine\Fields\Json;
use MoonShine\Fields\Markdown;
use MoonShine\Fields\Preview;
use MoonShine\Fields\Relationships\BelongsTo;
use MoonShine\Fields\Relationships\BelongsToMany;
use MoonShine\Fields\Switcher;
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
 * @extends ModelResource<Newsletter>
 */
class NewsletterResource extends ModelResource
{
    protected string $model = Newsletter::class;

    protected string $title = 'Рассылка';

    /**
     * @return list<MoonShineComponent|Field>
     */
    public function fields(): array
    {
        return [
            Block::make([
                ID::make()->sortable(),

                Date::make('Дата отправки', 'sent_at')
                    ->required()
                    ->withTime(),

                BelongsTo::make('Бот', 'bot')->required(),

                Text::make('Заголовок', 'title')
                    ->hint('Не используется при отправке')
                    ->required(),

                Markdown::make('Сообщение', 'message')
                    ->hideOnIndex()
                    ->hint('Доступные теги: {id},{first_name},{last_name},{username}'),

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

                BelongsToMany::make('Пользователи', 'newsletterUsers', resource: new UserResource())
                    ->hideOnIndex()
                    ->hint('Если пусто, то всем')
                    ->asyncSearch('username')
                    ->selectMode(),

                Switcher::make('Активен', 'is_active')->default(true),

                Preview::make('Отправлено', 'status', formatted: static fn(Newsletter $model) => $model->status === DispatchStatus::SENT)->boolean()
            ]),
        ];
    }

    /**
     * @param Newsletter $item
     *
     * @return array<string, string[]|string>
     * @see https://laravel.com/docs/validation#available-validation-rules
     */
    public function rules(Model $item): array
    {
        return [
            'title' => ['required', 'string'],
            'sent_at' => ['required'],
        ];
    }

    public function filters(): array
    {
        return [
            Switcher::make('Активен', 'is_active'),
            Enum::make('Статус', 'status')->attach(DispatchStatus::class),
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
