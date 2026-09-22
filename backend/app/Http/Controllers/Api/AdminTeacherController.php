<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Identity\Actions\AssignTeacherToCourse;
use App\Domain\Identity\Actions\CalculateTeacherBalance;
use App\Domain\Identity\Actions\RecordTeacherPayout;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminTeacherController extends Controller
{
    public function index(CalculateTeacherBalance $calculateBalance): JsonResponse
    {
        $this->authorizeAdmin();

        $teachers = User::role('teacher')
            ->with('taughtCourses')
            ->get()
            ->map(function (User $teacher) use ($calculateBalance): array {
                $balance = $calculateBalance->handle($teacher);

                return [
                    'id' => $teacher->id,
                    'name' => $teacher->name,
                    'email' => $teacher->email,
                    'phone' => $teacher->phone,
                    'courses' => $teacher->taughtCourses->map(fn (Course $c): array => [
                        'id' => $c->id,
                        'slug' => $c->slug,
                        'title' => $c->getTranslations('title'),
                        'teacher_share_percent' => $c->pivot ? $c->pivot->getAttribute('teacher_share_percent') : $c->teacher_share_percent,
                    ])->all(),
                    'earnings' => $balance,
                ];
            });

        return response()->json(['data' => $teachers]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')],
            'password' => ['required', 'string', 'min:8'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $teacher = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'must_change_password' => true,
            'email_verified_at' => now(),
        ]);

        $teacher->assignRole('teacher');

        return response()->json(['data' => $teacher], 201);
    }

    public function assignCourse(Request $request, int $courseId, AssignTeacherToCourse $action): JsonResponse
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'teacher_id' => ['required', 'exists:users,id'],
            'teacher_share_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        /** @var Course $course */
        $course = Course::query()->findOrFail($courseId);
        /** @var User $teacher */
        $teacher = User::query()->findOrFail($validated['teacher_id']);

        $action->handle(
            $course,
            $teacher,
            isset($validated['teacher_share_percent']) ? (int) $validated['teacher_share_percent'] : null
        );

        return response()->json(['message' => 'Teacher assigned successfully.']);
    }

    public function recordPayout(Request $request, int $teacherId, RecordTeacherPayout $action): JsonResponse
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'amount_cents' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        /** @var User $teacher */
        $teacher = User::query()->findOrFail($teacherId);
        /** @var User $admin */
        $admin = $request->user();

        $payout = $action->handle(
            $teacher,
            (int) $validated['amount_cents'],
            $admin,
            $validated['note'] ?? null
        );

        return response()->json(['data' => $payout], 201);
    }

    private function authorizeAdmin(): void
    {
        /** @var User $user */
        $user = request()->user();
        if (! $user->hasRole(['superadmin', 'admin'])) {
            abort(403);
        }
    }
}
