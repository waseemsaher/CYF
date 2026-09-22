<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->json('title');
            $table->json('description');
            $table->string('cover_image_path')->nullable();
            $table->unsignedInteger('price_cents')->default(0);
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->bigInteger('telegram_chat_id')->nullable();
            $table->string('telegram_invite_link')->nullable();
            $table->unsignedTinyInteger('teacher_share_percent')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
