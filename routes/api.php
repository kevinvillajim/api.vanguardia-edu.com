<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\StudentCourseController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\ExpDateController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\CategoryController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Health check for API
Route::get('/health', function () {
    return response()->json([
        'status' => 'OK',
        'message' => 'API is working correctly',
        'version' => '1.0',
        'timestamp' => now()->toISOString(),
    ]);
});

// Debug route para verificar password_changed
Route::get('/debug/user/{id}', function ($id) {
    $user = App\Models\User::find($id);
    if (! $user) {
        return response()->json(['error' => 'User not found'], 404);
    }

    return response()->json([
        'id' => $user->id,
        'email' => $user->email,
        'password_changed' => $user->password_changed,
        'password_changed_type' => gettype($user->password_changed),
        'password_changed_comparison_0' => ($user->password_changed == 0),
        'password_changed_comparison_1' => ($user->password_changed == 1),
        'active' => $user->active,
    ]);
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');

// Authentication routes (public)
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
    
    // Protected auth routes
    Route::middleware('auth:api')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::post('change-password', [AuthController::class, 'changePassword']);
    });
});

// Student routes
Route::middleware(['auth:api'])->prefix('student')->group(function () {
    // Student dashboard
    Route::get('dashboard', [StudentCourseController::class, 'getDashboard']);
    
    // Course management for students
    Route::get('courses', [StudentCourseController::class, 'getCourses']);
    Route::get('courses/{courseId}', [StudentCourseController::class, 'getCourse']);
    Route::get('courses/{courseId}/view', [StudentCourseController::class, 'getCourseView']);
    Route::get('courses/{courseId}/progress', [StudentCourseController::class, 'getProgress']);
    Route::post('courses/{courseId}/enroll', [StudentCourseController::class, 'enroll']);
    
    // Progress tracking
    Route::post('courses/{courseId}/components/{componentId}/complete', [StudentCourseController::class, 'completeComponent']);
    Route::get('enrollments', [StudentCourseController::class, 'getEnrollments']);
    
    // Quiz functionality
    Route::post('quizzes/{quizId}/start', [StudentCourseController::class, 'startQuiz']);
    Route::post('quiz-attempts/{attemptId}/complete', [StudentCourseController::class, 'completeQuiz']);
    
    // Certificates
    Route::post('enrollments/{enrollmentId}/certificate', [StudentCourseController::class, 'generateCertificate']);
});

// Progress tracking routes (available to authenticated users)
Route::middleware(['auth:api'])->prefix('progress')->group(function () {
    // Get progress for a specific course/unit
    Route::get('{courseId}/{unitId}', [StudentCourseController::class, 'getUnitProgress']);
    
    // Update/insert progress
    Route::post('upsert', [StudentCourseController::class, 'upsertProgress']);
});

// Teacher routes
Route::middleware(['auth:api', 'role:3'])->prefix('teacher')->group(function () {
    // Teacher dashboard and stats
    Route::get('stats', [TeacherController::class, 'getStats']);
    Route::get('activity', [TeacherController::class, 'getRecentActivity']);
    Route::get('dashboard', [TeacherController::class, 'getDashboard']);
    Route::get('students', [TeacherController::class, 'getStudents']);
    
    // Course management
    Route::get('courses', [CourseController::class, 'myCourses']);
    Route::post('courses', [CourseController::class, 'store']);
    Route::get('courses/{courseId}', [CourseController::class, 'show']);
    Route::put('courses/{courseId}', [CourseController::class, 'update']);
    Route::delete('courses/{courseId}', [CourseController::class, 'destroy']);
    Route::get('courses/{courseId}/enrollments', [TeacherController::class, 'getCourseEnrollments']);
    
    // MVP Course building endpoints
    Route::post('courses/{courseId}/units', [CourseController::class, 'addUnit']);
    Route::delete('units/{unitId}', [CourseController::class, 'deleteUnit']);
    Route::post('units/{unitId}/modules', [CourseController::class, 'addModule']);
    Route::delete('modules/{moduleId}', [CourseController::class, 'deleteModule']);
    Route::post('modules/{moduleId}/components', [CourseController::class, 'addComponent']);
    Route::put('components/{componentId}', [CourseController::class, 'updateComponent']);
    Route::delete('components/{componentId}', [CourseController::class, 'deleteComponent']);
    Route::put('courses/{courseId}/publish', [CourseController::class, 'publish']);
    Route::post('courses/{courseId}/upload-banner', [CourseController::class, 'uploadBanner']);
    
    // Draft management routes
    Route::post('courses/{courseId}/draft', [CourseController::class, 'saveDraft']);
    Route::get('courses/{courseId}/draft', [CourseController::class, 'getLatestDraft']);
    Route::delete('courses/{courseId}/drafts/cleanup', [CourseController::class, 'cleanupDrafts']);
    
    // Category management for teachers (context of course creation)
    Route::post('categories', [CategoryController::class, 'storeForTeacher']);
    
    // TODO: Implementar estos métodos en CourseController
    // Route::post('courses/{courseId}/clone', [CourseController::class, 'cloneCourse']);
    // Route::get('courses/{courseId}/students', [CourseController::class, 'getCourseStudents']);
    // Route::get('courses/{courseId}/analytics', [CourseController::class, 'getCourseAnalytics']);
});

// File upload routes (authenticated users)
Route::middleware(['auth:api'])->prefix('files')->group(function () {
    Route::post('upload', [FileController::class, 'uploadFile']);
    Route::delete('{filename}', [FileController::class, 'deleteFile']);
});

// Admin routes
Route::middleware(['auth:api', 'role:1'])->prefix('admin')->group(function () {
    // User management
    Route::get('users', [AdminController::class, 'getUsers']);
    Route::post('users', [AdminController::class, 'createUser']);
    Route::get('users/{userId}', [AdminController::class, 'getUser']);
    Route::put('users/{userId}', [AdminController::class, 'updateUser']);
    Route::delete('users/{userId}', [AdminController::class, 'deleteUser']);
    Route::post('users/import', [AdminController::class, 'importUsers']);
    Route::put('users/{userId}/reset-password', [AdminController::class, 'resetUserPassword']);
    
    // Course management
    Route::get('courses', [AdminController::class, 'getAllCourses']);
    Route::get('dashboard', [AdminController::class, 'getDashboard']);
    
    // Category management
    Route::post('categories', [CategoryController::class, 'store']);
    Route::put('categories/{id}', [CategoryController::class, 'update']);
    Route::delete('categories/{id}', [CategoryController::class, 'destroy']);
    
    // Reports and analytics
    Route::get('reports/users', [AdminController::class, 'getUserReport']);
    Route::get('reports/courses', [AdminController::class, 'getCourseReport']);
    Route::get('system/health', [AdminController::class, 'getSystemHealth']);
    
    // System settings
    Route::get('system-settings', [AdminController::class, 'getSystemSettings']);
    Route::put('system-settings', [AdminController::class, 'updateSystemSettings']);
});

// Public course routes (for course catalog)
Route::prefix('courses')->group(function () {
    Route::get('/', [CourseController::class, 'index']);
    Route::get('/slug/{slug}', [CourseController::class, 'getBySlug']);
    Route::get('/{courseId}', [CourseController::class, 'show']);
    Route::get('/{courseId}/content', [CourseController::class, 'getCourseContent']);
    Route::get('/category/{categoryId}', [CourseController::class, 'getCoursesByCategory']);
});

// Public category routes
Route::prefix('categories')->group(function () {
    Route::get('/', [CategoryController::class, 'index']);
    Route::get('/{id}', [CategoryController::class, 'show']);
});

// Legacy routes for existing functionality
Route::middleware(['auth:api'])->group(function () {
    Route::get('expdates', [ExpDateController::class, 'index']);
    Route::get('expdates/{id}', [ExpDateController::class, 'show']);
    Route::put('expdates/curso/{id}', [ExpDateController::class, 'updateByCursoId']);
    Route::post('expdates', [ExpDateController::class, 'store']);
});

// Fallback for unimplemented routes
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'error' => 'API endpoint not found',
        'message' => 'The requested API endpoint is not available'
    ], 404);
});
