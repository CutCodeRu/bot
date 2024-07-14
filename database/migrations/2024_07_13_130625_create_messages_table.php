<?php

use App\Bot\Enums\DelayType;
use App\Models\Message;
use App\Models\MessageBus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @see Message
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', static function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(MessageBus::class)
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('title');

            $table->unsignedTinyInteger('delay')->default(0);
            $table->unsignedTinyInteger('delay_type')->default(DelayType::SECONDS->value);
            $table->string('delay_at')->nullable();

            $table->text('message')->nullable();
            $table->json('attachments')->nullable();
            $table->json('buttons')->nullable();

            $table->boolean('is_active')->default(true);
            $table->unsignedTinyInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
