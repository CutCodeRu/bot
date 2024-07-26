<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use App\Models\Message;
use Illuminate\Database\Eloquent\Model;
use App\Models\MessageBus;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use MoonShine\Components\FlexibleRender;
use MoonShine\Components\Icon;
use MoonShine\Components\TableBuilder;
use MoonShine\Decorations\Tab;
use MoonShine\Fields\Position;
use MoonShine\Fields\Preview;
use MoonShine\Fields\Relationships\BelongsTo;
use MoonShine\Fields\Relationships\HasMany;
use MoonShine\Fields\Switcher;
use MoonShine\Fields\Td;
use MoonShine\Fields\Text;
use MoonShine\Handlers\ExportHandler;
use MoonShine\Handlers\ImportHandler;
use MoonShine\Http\Responses\MoonShineJsonResponse;
use MoonShine\MoonShineRequest;
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
                    ->fields([
                        Preview::make(formatted: fn($value, int $index) => !$this->isNowOnForm() ? $index + 1 : Icon::make('heroicons.outline.bars-4')),
                        Text::make('Заголовок', 'title'),
                        Td::make('Задержка', function (Message $message) {
                            return [
                                FlexibleRender::make(sprintf('%d (%s)', $message->delay, $message->delay_type->toString())),
                            ];
                        }),
                        Switcher::make('Активно', 'is_active'),
                    ])
                    ->modifyTable(
                        fn(TableBuilder $table, bool $preview) => $preview
                            ? $table
                            : $table->sortable($this->asyncMethodUrl('reorder', params: ['resourceItem' => $this->getItemID()]))
                    )
                    ->async()
                    ->creatable(),


                HasMany::make('События', 'eventMessages')
                    ->fields([
                        ID::make(),
                        Text::make('Заголовок', 'title'),
                    ])
                    ->async()
                    ->creatable()
            ]),
        ];
    }

    public function reorder(MoonShineRequest $request): void
    {
        /**
         * @var MessageBus $item
         */
        $item = $request->getResource()?->getItem();

        if($request->str('data')->isNotEmpty()) {
            $caseStatement = $request->str('data')
                 ->explode(',')
                 ->implode(fn($id, $position) => " WHEN $id THEN $position+1")
            ;

            $item->messages->each(fn(Message $message) => $message->update([
                'position' => DB::raw("CASE id $caseStatement END")
            ]));
        }
    }

    public function prepareForValidation(): void
    {
        if(is_null($this->getItemID()) && MessageBus::query()->where('bot_id', request()->integer('bot_id'))->exists()) {
            throw ValidationException::withMessages([
                'bot_id' => 'У данного бота уже есть цепочка сообщений',
            ])->errorBag($this->uriKey());
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
