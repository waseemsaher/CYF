<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminStudentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User $admin */
        $admin = $request->user();
        if (! $admin->hasRole(['superadmin', 'admin']) && ! $admin->can('students.manage')) {
            abort(403);
        }

        $query = User::role('student')->with(['roles']);

        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('telegram_username', 'like', "%{$search}%");
            });
        }

        if ($branch = $request->input('branch')) {
            $query->where('branch', $branch);
        }

        if ($academicYear = $request->input('academic_year')) {
            $query->where('academic_year', $academicYear);
        }

        if ($department = $request->input('department')) {
            $query->where('department', $department);
        }

        $students = $query->paginate(20);

        return response()->json($students);
    }
}
