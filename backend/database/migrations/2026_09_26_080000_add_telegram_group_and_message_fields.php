<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table): void {
            $table->renameColumn('telegram_chat_id', 'telegram_channel_id');
        });

        Schema::table('courses', function (Blueprint $table): void {
            $table->bigInteger('telegram_group_id')->nullable()->after('telegram_channel_id');
            $table->index('telegram_channel_id');
            $table->index('telegram_group_id');
        });

        Schema::table('course_items', function (Blueprint $table): void {
            $table->unsignedBigInteger('telegram_message_id')->nullable()->after('quiz_id');
        });
    }

    public function down(): void
    {
        Schema::table('course_items', function (Blueprint $table): void {
            $table->dropColumn('telegram_message_id');
        });

        Schema::table('courses', function (Blueprint $table): void {
            $table->dropIndex(['telegram_group_id']);
            $table->dropIndex(['telegram_channel_id']);
            $table->dropColumn('telegram_group_id');
        });

        Schema::table('courses', function (Blueprint $table): void {
            $table->renameColumn('telegram_channel_id', 'telegram_chat_id');
        });
    }
};
