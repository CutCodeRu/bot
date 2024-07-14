<?php

namespace App\Console\Commands;

use App\Bot\Entities\TargetUser;
use App\Bot\Enums\DispatchStatus;
use App\Bot\MessageFactory;
use App\Models\Bot;
use App\Models\Newsletter;
use App\Models\User;
use App\Services\BotMessageSender;
use App\Services\BotStorage;
use Illuminate\Console\Command;
use Throwable;

class NewsletterSenderCommand extends Command
{
    protected $signature = 'app:newsletter-sender';

    public function handle(): int
    {
        $items = Newsletter::query()
            ->dispatchNow(withSeconds: false)
            ->with(['bot', 'newsletterUsers'])
            ->get();

        $allUsers = User::query()->get();

        foreach ($items as $item) {
            $users = $item->newsletterUsers;

            if($users->isEmpty()) {
                $users = $allUsers;
            }

            $limiter = 0;

            foreach ($users as $user) {
                if($limiter === 20) {
                    sleep(1);
                    $limiter = 0;
                }

                /** @var Bot $bot */
                $bot = $item->bot;
                $core = $bot->getBotCore();
                $chatId = $user->chats[$bot->getKey()] ?? null;

                if($chatId === null) {
                    continue;
                }

                $msg = (new MessageFactory(
                    $chatId,
                    $bot->getKey(),
                    null,
                    null,
                    new TargetUser($user->getKey(), $user->first_name, $user->last_name, $user->username),
                    $item->message,
                    $item->attachments,
                    $item->buttons,
                ))->withTags();

                try {
                    (new BotMessageSender($core, $msg, new BotStorage()))->send();

                    $item->status = DispatchStatus::SENT;
                    $item->is_active = false;
                } catch (Throwable $e) {
                    $item->status = DispatchStatus::FAILED;
                    $item->errors = $e->getMessage();
                }

                $item->save();

                $limiter++;
            }
        }

        return self::SUCCESS;
    }
}
