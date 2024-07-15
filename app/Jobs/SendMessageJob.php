<?php

namespace App\Jobs;

use App\Bot\BotCore;
use App\Bot\MessageFactory;
use App\Models\Bot;
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
        private MessageFactory $message,
    ) {

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

        Redis::throttle("send_message:{$this->message->getBotId()}")
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
        $bot = Bot::find($this->message->getBotId());
        $core = new BotCore($bot->token);

        (new BotMessageSender($core, $this->message, new BotStorage()))->send();
    }
}
