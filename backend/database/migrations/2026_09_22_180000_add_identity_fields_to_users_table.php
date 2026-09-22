<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('branch')->nullable();
            $table->string('academic_year')->nullable();
            $table->string('department')->nullable();
            $table->string('telegram_username')->nullable();
            $table->string('phone')->nullable();
            $table->string('locale', 10)->default('ar');
            $table->boolean('must_change_password')->default(false);
            $table->boolean('is_active')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'branch',
                'academic_year',
                'department',
                'telegram_username',
                'phone',
                'locale',
                'must_change_password',
                'is_active',
            ]);
        });
    }
};
