<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_course_invites', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            // The actual invite link URL returned by Telegram's createChatInviteLink
            $table->string('invite_link', 512);
            $table->timestamp('expires_at');
            // NULL means Telegram hasn't confirmed it was used yet; will be set when we know it's spent
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            // Ensure we can quickly look up the current outstanding invite for a user+course pair
            $table->index(['user_id', 'course_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_course_invites');
    }
};
