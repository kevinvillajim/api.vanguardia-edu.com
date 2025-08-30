<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\StudentCourseController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V2 Routes
|--------------------------------------------------------------------------
|
| Clean Architecture API routes with proper structure and error handling
|
*/

// Health check for V2 API
Route::get('/health', function () {
    return response()->json([
        'status' => 'OK',
        'message' => 'API V2 is working correctly',
        'version' => '2.0',
        'timestamp' => now()->toISOString(),
    ]);
});

// Authentication routes (public)
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
    
    // Protected auth routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::post('change-password', [AuthController::class, 'changePassword']);
    });
});

// Student routes
Route::middleware(['auth:sanctum'])->prefix('student')->group(function () {
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

// Teacher routes
Route::middleware(['auth:sanctum', 'role:3'])->prefix('teacher')->group(function () {
    Route::get('courses', [CourseController::class, 'myCourses']);
    Route::get('analytics', [TeacherController::class, 'getAnalytics']);
    Route::post('courses', [CourseController::class, 'createCourse']);
    Route::get('courses/{courseId}', [CourseController::class, 'getCourse']);
    Route::put('courses/{courseId}', [CourseController::class, 'updateCourse']);
    Route::delete('courses/{courseId}', [CourseController::class, 'deleteCourse']);
    Route::post('courses/{courseId}/clone', [CourseController::class, 'cloneCourse']);
    
    // Course analytics and management
    Route::get('courses/{courseId}/students', [CourseController::class, 'getCourseStudents']);
    Route::get('courses/{courseId}/analytics', [CourseController::class, 'getCourseAnalytics']);
});

// Admin routes
Route::middleware(['auth:sanctum', 'role:1'])->prefix('admin')->group(function () {
    // User management
    Route::get('users', [AdminController::class, 'getUsers']);
    Route::post('users', [AdminController::class, 'createUser']);
    Route::get('users/{userId}', [AdminController::class, 'getUser']);
    Route::put('users/{userId}', [AdminController::class, 'updateUser']);
    Route::delete('users/{userId}', [AdminController::class, 'deleteUser']);
    Route::post('users/import', [AdminController::class, 'importUsers']);
    Route::put('users/{userId}/reset-password', [AdminController::class, 'resetUserPassword']);
    
    // Course management
    Route::get('courses', [CourseController::class, 'getAllCourses']);
    Route::get('dashboard', [AdminController::class, 'getDashboard']);
    
    // System settings
    Route::get('system-settings', [AdminController::class, 'getSystemSettings']);
    Route::put('system-settings', [AdminController::class, 'updateSystemSettings']);
});

// Public course routes (for course catalog)
Route::prefix('courses')->group(function () {
    Route::get('/', [CourseController::class, 'getPublicCourses']);
    Route::get('/{courseId}', [CourseController::class, 'getPublicCourse']);
    Route::get('/category/{categoryId}', [CourseController::class, 'getCoursesByCategory']);
});

// Fallback for unimplemented routes
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'error' => 'API endpoint not found',
        'message' => 'The requested API v2 endpoint is not yet implemented'
    ], 404);
});