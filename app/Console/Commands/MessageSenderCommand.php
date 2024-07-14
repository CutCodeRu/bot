<?php

namespace App\Console\Commands;

use App\Bot\Enums\DispatchStatus;
use App\Bot\MessageFactory;
use App\Models\Bot;
use App\Models\MessageSchedule;
use App\Services\BotMessageSender;
use App\Services\BotStorage;
use Illuminate\Console\Command;
use Throwable;

class MessageSenderCommand extends Command
{
    protected $signature = 'app:message-sender';

    public function handle(): int
    {
        $items = MessageSchedule::query()
            ->dispatchNow(withSeconds: false)
            ->with(['bot'])
            ->get();


        $limiter = 0;

        foreach ($items as $item) {
            if ($limiter === 20) {
                sleep(1);
                $limiter = 0;
            }

            /** @var Bot $bot */
            $bot = $item->bot;
            $core = $bot->getBotCore();

            try {
                (new BotMessageSender($core, MessageFactory::fromArray($item->payload), new BotStorage()))->send();

                $item->status = DispatchStatus::SENT;
            } catch (Throwable $e) {
                $item->status = DispatchStatus::FAILED;
                $item->error = $e->getMessage();
            }

            $item->save();

            $limiter++;
        }

        return self::SUCCESS;
    }
}
