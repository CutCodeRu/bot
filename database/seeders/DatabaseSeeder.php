<?php

namespace Database\Seeders;

use App\Bot\Enums\DelayType;
use App\Models\Bot;
use App\Models\Newsletter;
use Illuminate\Database\Seeder;
use MoonShine\Models\MoonshineUser;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        MoonshineUser::query()->create([
            'email' => 'admin@cutcode.dev',
            'name' => 'admin@cutcode.dev',
            'password' => bcrypt('123456'),
        ]);

        $bot = Bot::query()->create([
            'name' => config('bot.test.username'),
            'token' => config('bot.test.token'),
        ]);

        $bus = $bot->messageBuses()->create([
            'title' => 'Testing',
            'is_active' => true,
        ]);

        $bus->messages()->create([
            'title' => 'First',
            'message' => 'First message to {first_name} {last_name}',
            'delay' => 0,
            'delay_type' => DelayType::SECONDS,
            'attachments' => [],
            'buttons' => [
                ['text' => 'Go', 'url' => 'https://cutcode.dev'],
            ],
            'is_active' => true,
            'position' => 1,
        ]);

        $bus->messages()->create([
            'title' => 'Second',
            'message' => 'Second message to {id} {username}',
            'delay' => 120,
            'delay_type' => DelayType::SECONDS,
            'attachments' => [],
            'buttons' => [],
            'is_active' => true,
            'position' => 2,
        ]);

        $bus->messages()->create([
            'title' => 'Third',
            'message' => 'Third message',
            'delay' => 7,
            'delay_type' => DelayType::DAYS,
            'delay_at' => '14:00',
            'attachments' => [],
            'buttons' => [],
            'is_active' => true,
            'position' => 3,
        ]);

        Newsletter::query()->create([
            'bot_id' => $bot->getKey(),
            'title' => 'Testing',
            'sent_at' => now()->addMinutes(3),
            'message' => 'Message ```php echo 1;```',
            'attachments' => [],
            'buttons' => [],
            'is_active' => true,
        ]);
    }
}
