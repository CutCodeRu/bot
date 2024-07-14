<?php

namespace App\Jobs;

use App\Bot\Entities\TargetUser;
use App\Bot\MessageFactory;
use App\Models\Bot;
use App\Models\Message;
use App\Models\MessageSchedule;
use App\Models\User;
use App\Services\BotMessageSender;
use App\Services\BotStorage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;

class SendMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public $tries = 1;

    public $timeout = 10;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly int $chatId,
        private readonly Bot $bot,
        private readonly User $user,
        private readonly Message $message,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (config('queue.default') !== 'redis' || app()->isLocal()) {
            $this->send();

            return;
        }

        Redis::throttle("send_message:{$this->bot->getKey()}")
            ->block(0)
            ->allow(1)
            ->every(5)
            ->then(
                fn () => $this->send(),
                fn () => $this->release(2)
            );
    }

    private function send(): void
    {
        $core = $this->bot->getBotCore();

        $msg = (new MessageFactory(
            $this->chatId,
            $this->bot->getKey(),
            $this->message->message_bus_id,
            $this->message->position,
            new TargetUser(
                $this->user->getKey(), $this->user->first_name, $this->user->last_name, $this->user->username
            ),
            $this->message->message,
            $this->message->attachements,
            $this->message->buttons,
        ))->withTags();

        (new BotMessageSender($core, $msg, new BotStorage()))->send();
    }
}
