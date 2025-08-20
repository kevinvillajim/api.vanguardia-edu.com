<?php

use App\Http\Controllers\RefactoredAuthController;
use App\Http\Controllers\RefactoredUserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes V2 - Refactored Clean Architecture
|--------------------------------------------------------------------------
|
| New API routes using Clean Architecture with DTOs, Services, and Repositories
|
*/

// Health check
Route::get('/health', function () {
    return response()->json([
        'status' => 'OK',
        'message' => 'API V2 is working correctly',
        'version' => '2.0.0',
        'architecture' => 'Clean Architecture',
        'timestamp' => now()->toISOString(),
    ]);
});

// Authentication routes (public) - rate limiting temporarily disabled for testing
Route::prefix('auth')->group(function () {
    Route::post('login', [RefactoredAuthController::class, 'login']);
    Route::post('register', [RefactoredAuthController::class, 'register']);
});

// TODO: Re-enable rate limiting after testing
// Route::prefix('auth')->middleware(['rate.limit.advanced:auth.login'])->group(function () {
//     Route::post('login', [RefactoredAuthController::class, 'login']);
// });
// Route::prefix('auth')->middleware(['rate.limit.advanced:auth.register'])->group(function () {
//     Route::post('register', [RefactoredAuthController::class, 'register']);
// });

// Protected routes
Route::middleware(['auth:api', 'rate.limit.advanced:api.general'])->group(function () {

    // Authentication
    Route::prefix('auth')->group(function () {
        Route::get('me', [RefactoredAuthController::class, 'me']);
        Route::post('logout', [RefactoredAuthController::class, 'logout']);
        Route::post('refresh', [RefactoredAuthController::class, 'refresh']);
        Route::put('profile', [RefactoredAuthController::class, 'updateProfile']);
        Route::put('change-password', [RefactoredAuthController::class, 'changePassword']);
    });

    // Users (general endpoints)
    Route::prefix('users')->group(function () {
        Route::get('search', [RefactoredUserController::class, 'search']);
        Route::get('{id}', [RefactoredUserController::class, 'show']);
    });

    // Admin only routes
    Route::middleware(['role:1', 'rate.limit.advanced:admin.general'])->group(function () {

        // User management
        Route::prefix('users')->group(function () {
            Route::get('/', [RefactoredUserController::class, 'index']);
            Route::post('/', [RefactoredUserController::class, 'store']);
            Route::put('{id}', [RefactoredUserController::class, 'update']);
            Route::delete('{id}', [RefactoredUserController::class, 'destroy']);
            Route::put('{id}/reset-password', [RefactoredUserController::class, 'resetPassword']);
            Route::put('{id}/set-active', [RefactoredUserController::class, 'setActive']);
            Route::get('role/{role}', [RefactoredUserController::class, 'getByRole']);
            Route::post('import', [RefactoredUserController::class, 'import']);
        });

        // Statistics
        Route::get('statistics/users', [RefactoredAuthController::class, 'getUserStatistics']);
    });
});

// RUTAS DE CURSOS V2
Route::prefix('courses')->group(function () {
    // Rutas públicas
    Route::get('/', [\App\Http\Controllers\Api\V2\CourseController::class, 'index']);
    Route::get('/featured', [\App\Http\Controllers\Api\V2\CourseController::class, 'featured']);
    Route::get('/search', [\App\Http\Controllers\Api\V2\CourseController::class, 'search']);
    Route::get('/{slug}', [\App\Http\Controllers\Api\V2\CourseController::class, 'show']);

    // Rutas autenticadas
    Route::middleware(['auth:api'])->group(function () {
        // Rutas para profesores
        Route::middleware(['role:3'])->group(function () {
            Route::post('/', [\App\Http\Controllers\Api\V2\CourseController::class, 'store']);
            Route::put('/{id}', [\App\Http\Controllers\Api\V2\CourseController::class, 'update']);
            Route::delete('/{id}', [\App\Http\Controllers\Api\V2\CourseController::class, 'destroy']);
            Route::post('/{id}/publish', [\App\Http\Controllers\Api\V2\CourseController::class, 'publish']);
            Route::post('/{id}/unpublish', [\App\Http\Controllers\Api\V2\CourseController::class, 'unpublish']);
            Route::get('/teacher/my-courses', [\App\Http\Controllers\Api\V2\CourseController::class, 'myCourses']);

            // Gestión de estudiantes en cursos
            Route::get('/{id}/students', [\App\Http\Controllers\Api\V2\CourseController::class, 'getEnrolledStudents']);
            Route::post('/{id}/students', [\App\Http\Controllers\Api\V2\CourseController::class, 'assignStudent']);
            Route::delete('/{id}/students/{userId}', [\App\Http\Controllers\Api\V2\CourseController::class, 'removeStudent']);
        });

        // Rutas para estudiantes
        Route::middleware(['role:2'])->group(function () {
            Route::post('/{id}/enroll', [\App\Http\Controllers\Api\V2\CourseController::class, 'enroll']);
            Route::get('/student/my-enrollments', [\App\Http\Controllers\Api\V2\CourseController::class, 'myEnrollments']);
        });
    });
});

// SISTEMA DE CURSOS INTERACTIVOS
Route::middleware(['auth:api'])->group(function () {

    // Rutas para estudiantes
    Route::middleware(['role:2'])->prefix('student')->group(function () {
        Route::get('courses', [\App\Http\Controllers\Api\V2\InteractiveCourseController::class, 'getStudentCourses']);
        Route::get('courses/{courseId}/view', [\App\Http\Controllers\Api\V2\StudentCourseController::class, 'show']);
        Route::get('courses/{courseId}/progress', [\App\Http\Controllers\Api\V2\StudentCourseController::class, 'getProgress']);
        Route::get('courses/{courseId}/materials', [\App\Http\Controllers\Api\V2\StudentCourseController::class, 'getMaterials']);
        Route::get('courses/{courseId}/activities', [\App\Http\Controllers\Api\V2\StudentCourseController::class, 'getActivities']);
        Route::post('courses/{courseId}/components/{componentId}/complete', [\App\Http\Controllers\Api\V2\StudentCourseController::class, 'markComponentCompleted']);
        Route::put('courses/{courseId}/modules/{moduleId}/progress', [\App\Http\Controllers\Api\V2\StudentCourseController::class, 'updateModuleProgress']);

        // Quizzes
        Route::post('quizzes/{quizId}/start', [\App\Http\Controllers\Api\V2\InteractiveCourseController::class, 'startQuizAttempt']);
        Route::post('quiz-attempts/{attemptId}/complete', [\App\Http\Controllers\Api\V2\InteractiveCourseController::class, 'completeQuizAttempt']);

        // Certificados
        Route::post('enrollments/{enrollmentId}/certificate', [\App\Http\Controllers\Api\V2\InteractiveCourseController::class, 'generateCertificate']);
    });

    // Rutas para profesores
    Route::middleware(['role:3,1'])->prefix('teacher')->group(function () {
        Route::get('courses', [\App\Http\Controllers\Api\V2\InteractiveCourseController::class, 'getTeacherCourses']);
        Route::post('courses/{courseId}/clone', [\App\Http\Controllers\Api\V2\InteractiveCourseController::class, 'cloneCourse']);
        
        // Estadísticas de profesor
        Route::get('stats', function() {
            return response()->json([
                'success' => true,
                'data' => [
                    'total_courses' => 0,
                    'total_students' => 0,
                    'active_courses' => 0,
                    'pending_reviews' => 0
                ]
            ]);
        });
        
        // Actividad del profesor
        Route::get('activity', function() {
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        });
    });

    // Rutas para administradores
    Route::middleware(['role:1'])->prefix('admin')->group(function () {
        Route::get('dashboard', [\App\Http\Controllers\Api\V2\AdminController::class, 'getDashboard']);
        Route::get('courses', [\App\Http\Controllers\Api\V2\AdminController::class, 'getAllCourses']);

        // Configuración del sistema
        Route::get('system-settings', [\App\Http\Controllers\Api\V2\AdminController::class, 'getSystemSettings']);
        Route::put('system-settings', [\App\Http\Controllers\Api\V2\AdminController::class, 'updateSystemSettings']);

        // Estadísticas
        Route::get('stats/certifications', [\App\Http\Controllers\Api\V2\AdminController::class, 'getCertificationStats']);
        Route::get('stats/clones', [\App\Http\Controllers\Api\V2\AdminController::class, 'getCloneStats']);

        // Gestión de certificados
        Route::put('certificates/{certificateId}/invalidate', [\App\Http\Controllers\Api\V2\AdminController::class, 'invalidateCertificate']);
    });
});
