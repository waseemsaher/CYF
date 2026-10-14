<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdminUserController extends Controller
{
    /**
     * Delete an individual user (student, teacher, or other admin).
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        /** @var User $admin */
        $admin = $request->user();

        if (! $admin->hasRole(['superadmin', 'admin']) && ! $admin->can('students.manage')) {
            abort(403, 'Unauthorized.');
        }

        if ($admin->id === $user->id) {
            return response()->json([
                'message' => 'لا يمكنك حذف حسابك الشخصي.',
            ], 422);
        }

        if ($user->hasRole('superadmin')) {
            if (! $admin->hasRole('superadmin')) {
                return response()->json([
                    'message' => 'لا يمكن لغير السوبر أدمن حذف حساب سوبر أدمن.',
                ], 403);
            }

            if (User::role('superadmin')->count() <= 1) {
                return response()->json([
                    'message' => 'لا يمكن حذف السوبر أدمن الأخير في المنصة.',
                ], 422);
            }
        }

        $disk = config('filesystems.default', 'local');
        $userName = (string) $user->name;
        $userEmail = (string) $user->email;
        $userRole = (string) ($user->roles->pluck('name')->first() ?? 'user');

        DB::transaction(function () use ($user, $disk, $admin, $userName, $userEmail, $userRole): void {
            // Delete payment proof files from storage
            $payments = Payment::where('user_id', $user->id)->get();
            foreach ($payments as $payment) {
                $proofPath = $payment->getAttribute('proof_path');
                if (is_string($proofPath) && $proofPath !== '' && Storage::disk($disk)->exists($proofPath)) {
                    Storage::disk($disk)->delete($proofPath);
                }
            }

            // Revoke active API tokens
            $user->tokens()->delete();

            // Detach roles & permissions
            $user->roles()->detach();
            $user->permissions()->detach();

            // Delete user (cascades payments, enrollments, quiz attempts, telegram invites, course_teacher)
            $user->delete();

            activity('user')
                ->causedBy($admin)
                ->withProperties([
                    'deleted_user_id' => $user->id,
                    'name' => $userName,
                    'email' => $userEmail,
                    'role' => $userRole,
                ])
                ->log("Admin {$admin->name} deleted user {$userName} ({$userEmail}).");
        });

        return response()->json([
            'message' => 'تم حذف المستخدم بنجاح.',
        ]);
    }

    /**
     * Bulk delete all student accounts and their associated records.
     */
    public function resetStudents(Request $request): JsonResponse
    {
        /** @var User $admin */
        $admin = $request->user();

        if (! $admin->hasRole(['superadmin', 'admin'])) {
            abort(403, 'Unauthorized.');
        }

        $disk = config('filesystems.default', 'local');

        $students = User::role('student')
            ->whereDoesntHave('roles', function ($query): void {
                $query->whereIn('name', ['superadmin', 'admin', 'teacher']);
            })
            ->get();

        $count = $students->count();

        DB::transaction(function () use ($students, $disk, $admin, $count): void {
            foreach ($students as $student) {
                // Delete payment proof files
                $payments = Payment::where('user_id', $student->id)->get();
                foreach ($payments as $payment) {
                    $proofPath = $payment->getAttribute('proof_path');
                    if (is_string($proofPath) && $proofPath !== '' && Storage::disk($disk)->exists($proofPath)) {
                        Storage::disk($disk)->delete($proofPath);
                    }
                }

                $student->tokens()->delete();
                $student->roles()->detach();
                $student->permissions()->detach();
                $student->delete();
            }

            activity('user')
                ->causedBy($admin)
                ->withProperties(['deleted_students_count' => $count])
                ->log("Admin {$admin->name} reset and deleted all {$count} student accounts.");
        });

        return response()->json([
            'message' => "تم حذف كافة حسابات الطلاب بنجاح ({$count} طالب).",
            'count' => $count,
        ]);
    }
}
