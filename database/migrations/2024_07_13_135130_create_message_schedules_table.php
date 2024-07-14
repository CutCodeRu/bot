<?php

use App\Bot\Enums\DispatchStatus;
use App\Models\Bot;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_schedules', static function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(Bot::class)
                ->constrained()
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreignIdFor(User::class)
                ->constrained()
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->timestamp('sent_at')->nullable();
            $table->json('payload');

            $table->integer('status')->default(DispatchStatus::QUEUED->value);
            $table->text('errors')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_schedules');
    }
};
