<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('term_id')->constrained('terms')->cascadeOnDelete();
            $table->string('method', 100);
            $table->unsignedInteger('list_price_cents');
            $table->unsignedInteger('discount_cents')->default(0);
            $table->unsignedInteger('amount_due_cents');
            $table->string('sender_identifier', 255);
            $table->string('proof_path', 500);
            $table->string('proof_hash', 64)->index();
            $table->text('student_note')->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->unsignedTinyInteger('teacher_share_percent')->nullable();
            $table->unsignedInteger('teacher_share_cents')->nullable();
            $table->unsignedInteger('platform_share_cents')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'course_id', 'term_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
