<?php

use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CertificateController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\HomeworkController;
use App\Http\Controllers\Api\LessonController;
use App\Http\Controllers\Api\ModuleController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\VideoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Health check
Route::get('/health', fn() => response()->json(['status' => 'ok', 'timestamp' => now()]));

// ==================== AUTH ====================
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/refresh', [AuthController::class, 'refresh']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

// ==================== PUBLIC ROUTES ====================
Route::get('/certificates/verify/{certificateId}', [CertificateController::class, 'verify']);

// ==================== AUTHENTICATED ROUTES ====================
Route::middleware('auth:sanctum')->group(function () {
    // Video OTP (authenticated, not admin-only)
    Route::get('/videos/{uuid}/otp', [VideoController::class, 'getOtp']);
});

// ==================== STUDENT ROUTES ====================
Route::prefix('student')->middleware('auth:sanctum')->group(function () {
    Route::get('/courses', [StudentController::class, 'listCourses']);
    Route::get('/courses/{uuid}', [StudentController::class, 'showCourse']);
    Route::get('/lessons/{uuid}', [StudentController::class, 'showLesson']);
    Route::get('/activities/quizzes/{uuid}', [StudentController::class, 'getQuiz']);
    Route::get('/activities/flashcards/{uuid}', [StudentController::class, 'getFlashcardSet']);
    Route::get('/progress', [StudentController::class, 'getProgress']);
    Route::post('/lessons/{uuid}/complete', [StudentController::class, 'completeLesson']);
});

// ==================== ADMIN ROUTES ====================
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {

    // Users
    Route::get('/users/export', [UserController::class, 'exportCsv']);
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::get('/users/{uuid}', [UserController::class, 'show']);
    Route::put('/users/{uuid}', [UserController::class, 'update']);
    Route::patch('/users/{uuid}/status', [UserController::class, 'toggleStatus']);

    // Categories
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{uuid}', [CategoryController::class, 'update']);
    Route::delete('/categories/{uuid}', [CategoryController::class, 'destroy']);

    // Courses
    Route::get('/courses', [CourseController::class, 'index']);
    Route::post('/courses', [CourseController::class, 'store']);
    Route::get('/courses/{uuid}', [CourseController::class, 'show']);
    Route::put('/courses/{uuid}', [CourseController::class, 'update']);
    Route::delete('/courses/{uuid}', [CourseController::class, 'destroy']);
    Route::patch('/courses/{uuid}/publish', [CourseController::class, 'togglePublish']);

    // Modules
    Route::get('/modules/course/{courseUuid}', [ModuleController::class, 'listByCourse']);
    Route::post('/modules', [ModuleController::class, 'store']);
    Route::put('/modules/{uuid}', [ModuleController::class, 'update']);
    Route::delete('/modules/{uuid}', [ModuleController::class, 'destroy']);
    Route::patch('/modules/reorder', [ModuleController::class, 'reorder']);

    // Lessons
    Route::get('/lessons', [LessonController::class, 'listAll']);
    Route::get('/lessons/module/{moduleUuid}', [LessonController::class, 'listByModule']);
    Route::post('/lessons', [LessonController::class, 'store']);
    Route::put('/lessons/{uuid}', [LessonController::class, 'update']);
    Route::delete('/lessons/{uuid}', [LessonController::class, 'destroy']);

    // Videos
    Route::get('/videos', [VideoController::class, 'index']);
    Route::post('/videos/upload', [VideoController::class, 'getUploadCredentials']);
    Route::get('/videos/{uuid}', [VideoController::class, 'show']);
    Route::put('/videos/{uuid}', [VideoController::class, 'update']);
    Route::delete('/videos/{uuid}', [VideoController::class, 'destroy']);
    Route::patch('/videos/{uuid}/confirm', [VideoController::class, 'confirmUpload']);
    Route::patch('/videos/{uuid}/link', [VideoController::class, 'linkToLesson']);

    // Activities — Quizzes
    Route::get('/activities/quizzes', [ActivityController::class, 'listQuizzes']);
    Route::post('/activities/quizzes', [ActivityController::class, 'createQuiz']);
    Route::get('/activities/quizzes/{uuid}', [ActivityController::class, 'getQuiz']);
    Route::put('/activities/quizzes/{uuid}', [ActivityController::class, 'updateQuiz']);
    Route::delete('/activities/quizzes/{uuid}', [ActivityController::class, 'deleteQuiz']);

    // Activities — Flashcards
    Route::get('/activities/flashcards', [ActivityController::class, 'listFlashcardSets']);
    Route::post('/activities/flashcards', [ActivityController::class, 'createFlashcardSet']);
    Route::get('/activities/flashcards/{uuid}', [ActivityController::class, 'getFlashcardSet']);
    Route::put('/activities/flashcards/{uuid}', [ActivityController::class, 'updateFlashcardSet']);
    Route::delete('/activities/flashcards/{uuid}', [ActivityController::class, 'deleteFlashcardSet']);

    // Activities — Drag & Drop
    Route::get('/activities/dragdrop', [ActivityController::class, 'listDragDropActivities']);
    Route::post('/activities/dragdrop', [ActivityController::class, 'createDragDropActivity']);
    Route::get('/activities/dragdrop/{uuid}', [ActivityController::class, 'getDragDropActivity']);
    Route::put('/activities/dragdrop/{uuid}', [ActivityController::class, 'updateDragDropActivity']);
    Route::delete('/activities/dragdrop/{uuid}', [ActivityController::class, 'deleteDragDropActivity']);

    // Homework
    Route::get('/homework', [HomeworkController::class, 'index']);
    Route::post('/homework', [HomeworkController::class, 'store']);
    Route::get('/homework/submissions', [HomeworkController::class, 'listSubmissions']);
    Route::get('/homework/submissions/{uuid}', [HomeworkController::class, 'getSubmission']);
    Route::patch('/homework/submissions/{uuid}/review', [HomeworkController::class, 'reviewSubmission']);
    Route::get('/homework/{uuid}', [HomeworkController::class, 'show']);
    Route::put('/homework/{uuid}', [HomeworkController::class, 'update']);
    Route::delete('/homework/{uuid}', [HomeworkController::class, 'destroy']);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications', [NotificationController::class, 'store']);
    Route::get('/notifications/{uuid}', [NotificationController::class, 'show']);
    Route::put('/notifications/{uuid}', [NotificationController::class, 'update']);
    Route::delete('/notifications/{uuid}', [NotificationController::class, 'destroy']);
    Route::patch('/notifications/{uuid}/send', [NotificationController::class, 'send']);

    // Payments — Plans
    Route::get('/payments/plans', [PaymentController::class, 'listPlans']);
    Route::post('/payments/plans', [PaymentController::class, 'createPlan']);
    Route::put('/payments/plans/{uuid}', [PaymentController::class, 'updatePlan']);
    Route::patch('/payments/plans/{uuid}/status', [PaymentController::class, 'togglePlanStatus']);

    // Payments — Transactions
    Route::get('/payments/transactions/export', [PaymentController::class, 'exportTransactionsCsv']);
    Route::get('/payments/transactions', [PaymentController::class, 'listTransactions']);
    Route::post('/payments/transactions', [PaymentController::class, 'createTransaction']);
    Route::get('/payments/transactions/{uuid}', [PaymentController::class, 'showTransaction']);
    Route::patch('/payments/transactions/{uuid}/approve', [PaymentController::class, 'approveTransaction']);
    Route::patch('/payments/transactions/{uuid}/reject', [PaymentController::class, 'rejectTransaction']);

    // Certificates — Templates
    Route::get('/certificates/templates', [CertificateController::class, 'listTemplates']);
    Route::post('/certificates/templates', [CertificateController::class, 'createTemplate']);
    Route::get('/certificates/templates/{uuid}', [CertificateController::class, 'getTemplate']);
    Route::put('/certificates/templates/{uuid}', [CertificateController::class, 'updateTemplate']);
    Route::delete('/certificates/templates/{uuid}', [CertificateController::class, 'deleteTemplate']);

    // Certificates
    Route::get('/certificates', [CertificateController::class, 'index']);
    Route::post('/certificates', [CertificateController::class, 'store']);
    Route::get('/certificates/{uuid}', [CertificateController::class, 'show']);
    Route::patch('/certificates/{uuid}/revoke', [CertificateController::class, 'revoke']);

    // Dashboard
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('/dashboard/recent-enrollments', [DashboardController::class, 'recentEnrollments']);
    Route::get('/dashboard/recent-activity', [DashboardController::class, 'recentActivity']);

    // Settings
    Route::get('/settings', [SettingsController::class, 'get']);
    Route::put('/settings', [SettingsController::class, 'update']);
});
