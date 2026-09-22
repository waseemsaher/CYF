<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discounts', function (Blueprint $table): void {
            $table->id();
            $table->json('name');
            $table->enum('type', ['percent', 'fixed']);
            $table->unsignedInteger('value');
            $table->enum('scope', ['all', 'courses']);
            $table->date('starts_at');
            $table->date('ends_at');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['is_active', 'starts_at', 'ends_at']);
        });

        Schema::create('discount_course', function (Blueprint $table): void {
            $table->foreignId('discount_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->primary(['discount_id', 'course_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discount_course');
        Schema::dropIfExists('discounts');
    }
};
