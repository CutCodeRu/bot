<?php

use App\Console\Commands\MessageSenderCommand;
use App\Console\Commands\NewsletterSenderCommand;
use Illuminate\Support\Facades\Schedule;

if(config('bot.use_newsletter', false)) {
    Schedule::command(NewsletterSenderCommand::class)->everyMinute();
}

Schedule::command(MessageSenderCommand::class)->everyMinute();
