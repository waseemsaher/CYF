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
            $table->index('status', 'courses_status_index');
        });

        Schema::table('enrollments', function (Blueprint $table): void {
            $table->index('status', 'enrollments_status_index');
            $table->index('expires_at', 'enrollments_expires_at_index');
        });

        Schema::table('attempt_answers', function (Blueprint $table): void {
            $table->index('question_id', 'attempt_answers_question_id_idx');
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->index(['status', 'created_at'], 'payments_status_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropIndex('payments_status_created_at_index');
        });

        Schema::table('attempt_answers', function (Blueprint $table): void {
            $table->dropIndex('attempt_answers_question_id_idx');
        });

        Schema::table('enrollments', function (Blueprint $table): void {
            $table->dropIndex('enrollments_status_index');
            $table->dropIndex('enrollments_expires_at_index');
        });

        Schema::table('courses', function (Blueprint $table): void {
            $table->dropIndex('courses_status_index');
        });
    }
};
