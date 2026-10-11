<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cta_blocks', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 64)->unique();
            $table->string('label_ar', 120);
            $table->string('headline_ar', 180)->nullable();
            $table->string('subtext_ar', 350)->nullable();
            $table->string('url', 500);
            $table->string('style', 24)->default('primary');
            $table->boolean('is_active')->default(true)->index();
            $table->dateTime('starts_at')->nullable()->index();
            $table->dateTime('ends_at')->nullable()->index();
            $table->boolean('open_in_new_tab')->default(false);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cta_blocks');
    }
};
