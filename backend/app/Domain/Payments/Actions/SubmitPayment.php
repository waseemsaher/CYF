<?php

declare(strict_types=1);

namespace App\Domain\Payments\Actions;

use App\Domain\Catalog\Actions\CalculateCoursePrice;
use App\Domain\Enrollment\Actions\ActivateEnrollment;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\Term;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SubmitPayment
{
    public function __construct(
        private readonly CalculateCoursePrice $calculateCoursePrice,
        private readonly ActivateEnrollment $activateEnrollment,
    ) {}

    /**
     * @param  array{method: string, sender_identifier: string, student_note?: string|null}  $data
     */
    public function handle(User $user, Course $course, Term $term, UploadedFile $proof, array $data): Payment|Enrollment
    {
        $proofPath = null;

        try {
            return DB::transaction(function () use ($user, $course, $term, $proof, $data, &$proofPath): Payment|Enrollment {
                User::query()
                    ->whereKey($user->getKey())
                    ->lockForUpdate()
                    ->first();

                // Check for existing active non-expired enrollment
                $existingActiveEnrollment = Enrollment::query()
                    ->where('user_id', $user->getKey())
                    ->where('course_id', $course->getKey())
                    ->where('term_id', $term->getKey())
                    ->where('status', 'active')
                    ->where(function ($query) {
                        $query->whereNull('expires_at')
                            ->orWhere('expires_at', '>', now());
                    })
                    ->exists();

                if ($existingActiveEnrollment) {
                    throw ValidationException::withMessages([
                        'course_id' => [__('You already have an active enrollment for this course in this term.')],
                    ]);
                }

                // Check for existing pending payment
                $existingPending = Payment::query()
                    ->where('user_id', $user->getKey())
                    ->where('course_id', $course->getKey())
                    ->where('term_id', $term->getKey())
                    ->where('status', 'pending')
                    ->exists();

                if ($existingPending) {
                    throw ValidationException::withMessages([
                        'course_id' => [__('You already have a pending payment for this course in this term.')],
                    ]);
                }

                // Calculate pricing
                $pricing = $this->calculateCoursePrice->handle($course);

                // If free → instant enrollment
                if ($pricing['amount_due_cents'] === 0) {
                    return $this->activateEnrollment->handle(
                        user: $user,
                        course: $course,
                        term: $term,
                        source: 'free',
                    );
                }

                // Process proof image
                $proofHash = hash_file('sha256', $proof->getRealPath());
                $proofPath = $this->storeProof($proof);

                return Payment::create([
                    'user_id' => $user->getKey(),
                    'course_id' => $course->getKey(),
                    'term_id' => $term->getKey(),
                    'method' => $data['method'],
                    'list_price_cents' => $pricing['list_price_cents'],
                    'discount_cents' => $pricing['discount_cents'],
                    'amount_due_cents' => $pricing['amount_due_cents'],
                    'sender_identifier' => $data['sender_identifier'],
                    'proof_path' => $proofPath,
                    'proof_hash' => $proofHash,
                    'student_note' => $data['student_note'] ?? null,
                    'status' => 'pending',
                ]);
            });
        } catch (\Throwable $e) {
            if ($proofPath !== null) {
                $disk = config('filesystems.default', 'local');
                Storage::disk($disk)->delete($proofPath);
            }

            throw $e;
        }
    }

    private function storeProof(UploadedFile $file): string
    {
        // Validate real MIME type
        $realMime = $file->getMimeType();
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];

        if (! in_array($realMime, $allowedMimes, true)) {
            throw ValidationException::withMessages([
                'proof' => [__('The proof image must be a JPEG, PNG, or WebP file.')],
            ]);
        }

        // Strip EXIF and re-encode using GD
        $image = match ($realMime) {
            'image/jpeg' => @imagecreatefromjpeg($file->getRealPath()),
            'image/png' => @imagecreatefrompng($file->getRealPath()),
            default => @imagecreatefromwebp($file->getRealPath()),
        };

        if ($image === false) {
            throw ValidationException::withMessages([
                'proof' => [__('The proof image could not be processed.')],
            ]);
        }

        // Generate a random filename and save as JPEG (strips EXIF)
        $filename = 'proofs/'.bin2hex(random_bytes(16)).'.jpg';
        $tempPath = tempnam(sys_get_temp_dir(), 'proof_');

        if ($tempPath === false) {
            imagedestroy($image);
            throw ValidationException::withMessages([
                'proof' => [__('Failed to process proof image.')],
            ]);
        }

        imagejpeg($image, $tempPath, 85);
        imagedestroy($image);

        $disk = config('filesystems.default', 'local');
        Storage::disk($disk)->put($filename, (string) file_get_contents($tempPath));
        unlink($tempPath);

        return $filename;
    }
}
