<?php

use App\Bot\Enums\MessageType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', static function (Blueprint $table) {
            $table->tinyInteger('type')->after('id')->default(MessageType::DEFAULT->value);
        });
    }

    public function down(): void
    {
        Schema::table('messages', static function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
