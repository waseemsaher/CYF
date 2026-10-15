<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->enum('type', ['percent', 'fixed']);
            $table->unsignedInteger('value');
            $table->enum('scope', ['all', 'courses'])->default('all');
            $table->unsignedInteger('max_redemptions')->nullable();
            $table->unsignedInteger('max_redemptions_per_user')->default(1);
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['code', 'is_active']);
        });

        Schema::create('coupon_course', function (Blueprint $table): void {
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->primary(['coupon_id', 'course_id']);
        });

        Schema::create('coupon_redemptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['coupon_id', 'user_id']);
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->foreignId('coupon_id')
                ->nullable()
                ->after('term_id')
                ->constrained('coupons')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropForeign(['coupon_id']);
            $table->dropColumn('coupon_id');
        });

        Schema::dropIfExists('coupon_redemptions');
        Schema::dropIfExists('coupon_course');
        Schema::dropIfExists('coupons');
    }
};
