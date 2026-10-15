<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\CouponRedemption;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\TeacherPayout;
use App\Models\TelegramCourseInvite;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ResetFinancialsCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'platform:reset-financials
                            {--force : Force the operation without confirmation prompt}';

    /**
     * @var string
     */
    protected $description = 'Reset all payments, subscriptions (enrollments), teacher earnings/payouts, and coupon redemptions platform-wide while preserving user accounts.';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('Are you sure you want to reset ALL payments, subscriptions, and teacher payouts platform-wide? This cannot be undone!')) {
            $this->warn('Operation cancelled.');

            return self::SUCCESS;
        }

        $this->info('Starting platform financials and subscriptions reset...');

        $disk = config('filesystems.default', 'local');

        DB::transaction(function () use ($disk): void {
            // 1. Delete physical payment proof files
            $payments = Payment::query()->whereNotNull('proof_path')->get();
            $deletedFilesCount = 0;
            foreach ($payments as $payment) {
                $proofPath = $payment->getAttribute('proof_path');
                if (is_string($proofPath) && $proofPath !== '' && Storage::disk($disk)->exists($proofPath)) {
                    Storage::disk($disk)->delete($proofPath);
                    $deletedFilesCount++;
                }
            }
            $this->line("- Deleted {$deletedFilesCount} payment proof files from storage.");

            // 2. Delete coupon redemptions
            $couponRedemptionsCount = CouponRedemption::query()->count();
            CouponRedemption::query()->delete();
            $this->line("- Cleared {$couponRedemptionsCount} coupon redemptions.");

            // 3. Delete teacher payouts
            $payoutsCount = TeacherPayout::query()->count();
            TeacherPayout::query()->delete();
            $this->line("- Cleared {$payoutsCount} teacher payout records.");

            // 4. Delete telegram course invites
            $invitesCount = TelegramCourseInvite::query()->count();
            TelegramCourseInvite::query()->delete();
            $this->line("- Cleared {$invitesCount} telegram course invite links.");

            // 5. Delete enrollments (subscriptions)
            $enrollmentsCount = Enrollment::query()->count();
            Enrollment::query()->delete();
            $this->line("- Cleared {$enrollmentsCount} course enrollments (subscriptions).");

            // 6. Delete all payment records
            $paymentsCount = Payment::query()->count();
            Payment::query()->delete();
            $this->line("- Cleared {$paymentsCount} payment records.");

            // 7. Audit log entry
            activity('payment')
                ->withProperties([
                    'payments_cleared' => $paymentsCount,
                    'enrollments_cleared' => $enrollmentsCount,
                    'payouts_cleared' => $payoutsCount,
                ])
                ->log('Platform financials and subscriptions reset: all payments, subscriptions, and teacher earnings/payouts cleared.');
        });

        $this->newLine();
        $this->info('✓ Successfully reset all payments, teacher earnings/payouts, and active subscriptions across the platform.');
        $this->info('All student and teacher accounts remain intact with 0 active subscriptions and 0 balances.');

        return self::SUCCESS;
    }
}
