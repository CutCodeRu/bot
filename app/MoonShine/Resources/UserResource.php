<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use App\Bot\Enums\DispatchStatus;
use App\Models\MessageHistory;
use App\Models\MessageSchedule;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use MoonShine\Components\MoonShineComponent;
use MoonShine\Components\TableBuilder;
use MoonShine\Decorations\Block;
use MoonShine\Fields\Date;
use MoonShine\Fields\Field;
use MoonShine\Fields\ID;
use MoonShine\Fields\Preview;
use MoonShine\Fields\Relationships\BelongsTo;
use MoonShine\Fields\Td;
use MoonShine\Fields\Text;
use MoonShine\Handlers\ExportHandler;
use MoonShine\Handlers\ImportHandler;
use MoonShine\Resources\ModelResource;
use MoonShine\TypeCasts\ModelCast;

/**
 * @extends ModelResource<User>
 */
class UserResource extends ModelResource
{
    protected string $model = User::class;

    protected string $title = 'Пользователи';

    protected string $column = 'username';

    public function getActiveActions(): array
    {
        return ['view'];
    }

    /**
     * @return list<MoonShineComponent|Field>
     */
    public function fields(): array
    {
        return [
            Block::make([
                ID::make()->sortable(),
                Text::make('Username'),
                Text::make('Имя', 'first_name'),
                Text::make('Фамилия', 'last_name'),
                Preview::make('Активный', 'is_active')->boolean(),

                Td::make('История сообщений', function (User $user) {
                    return [
                        TableBuilder::make()
                            ->fields([
                                Preview::make('Бот', 'bot.name'),
                                Text::make('Текст', 'payload.message'),
                                Date::make('Дата', 'created_at')->format('d.m.Y в H:i:s'),
                            ])
                            ->cast(ModelCast::make(MessageHistory::class))
                            ->items(
                                $user->messageHistories
                            ),
                    ];
                })->hideOnAll()->showOnDetail(),

                Td::make('Будут отправлены', function (User $user) {
                    return [
                        TableBuilder::make()
                            ->fields([
                                Preview::make('Бот', 'bot.name'),
                                Text::make('Текст', 'payload.message'),
                                Date::make('Дата', 'sent_at')->format('d.m.Y в H:i:s'),
                            ])
                            ->cast(ModelCast::make(MessageSchedule::class))
                            ->items(
                                $user->messageSchedules()
                                    ->where('status', DispatchStatus::QUEUED)
                                    ->get()
                            ),
                    ];
                })->hideOnAll()->showOnDetail(),
            ]),
        ];
    }

    /**
     * @param  User  $item
     *
     * @return array<string, string[]|string>
     * @see https://laravel.com/docs/validation#available-validation-rules
     */
    public function rules(Model $item): array
    {
        return [];
    }

    public function filters(): array
    {
        return [
            Text::make('ID'),
            Text::make('Username'),
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
