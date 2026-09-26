<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AdminCourseController;
use App\Http\Controllers\Api\AdminEnrollmentController;
use App\Http\Controllers\Api\AdminLearningController;
use App\Http\Controllers\Api\AdminOverviewController;
use App\Http\Controllers\Api\AdminPaymentController;
use App\Http\Controllers\Api\AdminSettingsController;
use App\Http\Controllers\Api\AdminStudentController;
use App\Http\Controllers\Api\AdminTeacherController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\ContentBlockController;
use App\Http\Controllers\Api\CourseContentController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\QuizController;
use App\Http\Controllers\Api\TeacherDashboardController;
use App\Http\Controllers\Api\TelegramController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/courses', [CatalogController::class, 'index']);
    Route::get('/courses/{slug}', [CatalogController::class, 'show']);
    Route::get('/courses/{slug}/content', [CourseContentController::class, 'show']);
    Route::get('/reference/academic-years', [CatalogController::class, 'academicYears']);
    Route::get('/reference/departments', [CatalogController::class, 'departments']);
    Route::get('/reference/terms', [CatalogController::class, 'terms']);
    Route::get('/content-blocks/{key}', [ContentBlockController::class, 'show']);

    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('throttle:10,1');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1');

    // Telegram bot webhook (rate-limited, protected by secret token header)
    Route::post('/telegram/webhook', [TelegramController::class, 'webhook'])
        ->middleware('throttle:60,1');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/me', [ProfileController::class, 'show']);
        Route::put('/profile', [ProfileController::class, 'update']);

        // Course learning content private downloads
        Route::get('/courses/{slug}/items/{itemId}/file', [CourseContentController::class, 'downloadFile']);

        // Quiz taking
        Route::prefix('quizzes/{quiz}')->group(function (): void {
            Route::post('/start', [QuizController::class, 'start']);
            Route::post('/attempts/{attempt}/submit', [QuizController::class, 'submit']);
            Route::get('/attempts/{attempt}', [QuizController::class, 'showAttempt']);
            Route::get('/my-attempts', [QuizController::class, 'myAttempts']);
        });

        // Teacher dashboard
        Route::prefix('teacher')->group(function (): void {
            Route::get('/dashboard', [TeacherDashboardController::class, 'dashboard']);
            Route::get('/courses/{course}/students', [TeacherDashboardController::class, 'courseStudents']);
            Route::get('/quizzes/{quiz}/analytics', [TeacherDashboardController::class, 'quizAnalytics']);
        });

        // Telegram student endpoints
        Route::prefix('telegram')->group(function (): void {
            Route::get('/status', [TelegramController::class, 'status']);
            Route::post('/link-token', [TelegramController::class, 'generateLinkToken']);
            Route::post('/unlink', [TelegramController::class, 'unlink']);
        });
        Route::get('/lessons/{item}/watch', [TelegramController::class, 'watchLesson']);

        // Student payments (rate-limited)
        Route::post('/payments', [PaymentController::class, 'store'])
            ->middleware('throttle:10,1');
        Route::get('/payments', [PaymentController::class, 'index']);
        Route::get('/payments/{id}', [PaymentController::class, 'show']);
        Route::post('/payments/{id}/cancel', [PaymentController::class, 'cancel']);

        Route::prefix('admin')->group(function (): void {
            // Overview dashboard
            Route::get('/overview', [AdminOverviewController::class, '__invoke']);

            // Student management
            Route::get('/students', [AdminStudentController::class, 'index']);

            // Teacher management & payouts
            Route::get('/teachers', [AdminTeacherController::class, 'index']);
            Route::post('/teachers', [AdminTeacherController::class, 'store']);
            Route::post('/courses/{course}/teachers', [AdminTeacherController::class, 'assignCourse']);
            Route::post('/teachers/{teacher}/payouts', [AdminTeacherController::class, 'recordPayout']);

            // Course management
            Route::get('/courses', [AdminCourseController::class, 'index']);
            Route::post('/courses', [AdminCourseController::class, 'store']);
            Route::put('/courses/{course}', [AdminCourseController::class, 'update']);
            Route::delete('/courses/{course}', [AdminCourseController::class, 'destroy']);
            Route::post('/courses/{course}/telegram/test', [AdminCourseController::class, 'testTelegramConnection']);

            // Learning content management
            Route::post('/courses/{course}/sections', [AdminLearningController::class, 'storeSection']);
            Route::put('/sections/{section}', [AdminLearningController::class, 'updateSection']);
            Route::delete('/sections/{section}', [AdminLearningController::class, 'destroySection']);

            Route::post('/courses/{course}/sections/{section}/items', [AdminLearningController::class, 'storeItem']);
            Route::put('/items/{item}', [AdminLearningController::class, 'updateItem']);
            Route::delete('/items/{item}', [AdminLearningController::class, 'destroyItem']);

            Route::post('/courses/{course}/quizzes', [AdminLearningController::class, 'storeQuiz']);
            Route::post('/quizzes/{quiz}/questions', [AdminLearningController::class, 'storeQuestion']);
            Route::delete('/questions/{question}', [AdminLearningController::class, 'destroyQuestion']);

            // Settings & Content blocks
            Route::get('/settings', [AdminSettingsController::class, 'index']);
            Route::put('/settings', [AdminSettingsController::class, 'update']);
            Route::get('/content-blocks', [ContentBlockController::class, 'index']);
            Route::put('/content-blocks/{key}', [ContentBlockController::class, 'update']);

            // Payment review queue
            Route::get('/payments', [AdminPaymentController::class, 'index']);
            Route::get('/payments/{id}', [AdminPaymentController::class, 'show']);
            Route::post('/payments/{id}/approve', [AdminPaymentController::class, 'approve']);
            Route::post('/payments/{id}/reject', [AdminPaymentController::class, 'reject']);

            // Enrollment management
            Route::post('/enrollments/grant', [AdminEnrollmentController::class, 'grant']);
            Route::post('/enrollments/{id}/revoke', [AdminEnrollmentController::class, 'revoke']);
            Route::put('/enrollments/{id}/extend', [AdminEnrollmentController::class, 'extend']);
        });
    });
});
