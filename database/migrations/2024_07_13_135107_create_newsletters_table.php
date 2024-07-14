<?php

use App\Bot\Enums\DispatchStatus;
use App\Models\Bot;
use App\Models\Newsletter;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletters', static function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(Bot::class)
                ->constrained()
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->string('title');

            $table->timestamp('sent_at')->nullable();

            $table->text('message')->nullable();
            $table->json('attachments')->nullable();
            $table->json('buttons')->nullable();

            $table->boolean('is_active')->default(true);

            $table->integer('status')->default(DispatchStatus::QUEUED->value);
            $table->text('errors')->nullable();

            $table->timestamps();
        });

        Schema::create('newsletter_user', static function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(Newsletter::class)
                ->constrained()
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreignIdFor(User::class)
                ->constrained()
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_user');
        Schema::dropIfExists('newsletters');
    }
};
