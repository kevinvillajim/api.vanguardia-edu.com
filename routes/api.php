<?php

use App\Http\Controllers\ExpDateController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Endpoint de salud (sin autenticación)
Route::get('/health', function () {
    return response()->json([
        'status' => 'OK',
        'message' => 'API is working correctly',
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
})->middleware('auth:sanctum');

// Legacy Authentication routes - DEPRECATED
// Please use /api/v2/auth/* endpoints instead
Route::group(['prefix' => 'auth'], function ($router) {
    Route::post('login', function () {
        return response()->json([
            'error' => 'This endpoint is deprecated. Please use /api/v2/auth/login',
            'redirect' => '/api/v2/auth/login',
        ], 410);
    });
    Route::post('register', function () {
        return response()->json([
            'error' => 'This endpoint is deprecated. Please use /api/v2/auth/register',
            'redirect' => '/api/v2/auth/register',
        ], 410);
    });
});

Route::middleware(['auth:api'])->group(function () {
    Route::post('me', function () {
        return response()->json([
            'error' => 'This endpoint is deprecated. Please use /api/v2/auth/me',
            'redirect' => '/api/v2/auth/me',
        ], 410);
    });
    Route::post('logout', function () {
        return response()->json([
            'error' => 'This endpoint is deprecated. Please use /api/v2/auth/logout',
            'redirect' => '/api/v2/auth/logout',
        ], 410);
    });
    Route::post('refresh', function () {
        return response()->json([
            'error' => 'This endpoint is deprecated. Please use /api/v2/auth/refresh',
            'redirect' => '/api/v2/auth/refresh',
        ], 410);
    });
    Route::post('edit-profile', function () {
        return response()->json([
            'error' => 'This endpoint is deprecated. Please use /api/v2/auth/profile',
            'redirect' => '/api/v2/auth/profile',
        ], 410);
    });
    Route::post('auth/change-password', function () {
        return response()->json([
            'error' => 'This endpoint is deprecated. Please use /api/v2/auth/change-password',
            'redirect' => '/api/v2/auth/change-password',
        ], 410);
    });
});

// Legacy Progress routes - DEPRECATED - Use V2 Interactive Course API
Route::get('progress', function () {
    return response()->json([
        'error' => 'This endpoint is deprecated. Please use /api/v2/student/courses for progress tracking',
        'redirect' => '/api/v2/student/courses',
    ], 410);
});
Route::post('progress', function () {
    return response()->json([
        'error' => 'This endpoint is deprecated. Please use V2 Interactive Course API',
        'redirect' => '/api/v2/student/courses',
    ], 410);
});
Route::get('progress/{id}', function () {
    return response()->json([
        'error' => 'This endpoint is deprecated. Please use V2 Interactive Course API',
        'redirect' => '/api/v2/student/courses',
    ], 410);
});
Route::put('progress/{id}', function () {
    return response()->json([
        'error' => 'This endpoint is deprecated. Please use V2 Interactive Course API',
        'redirect' => '/api/v2/student/courses',
    ], 410);
});
Route::get('progress/student/{id}', function () {
    return response()->json([
        'error' => 'This endpoint is deprecated. Please use V2 Interactive Course API',
        'redirect' => '/api/v2/student/courses',
    ], 410);
});
Route::get('progress/{id}/{course}', function () {
    return response()->json([
        'error' => 'This endpoint is deprecated. Please use V2 Interactive Course API',
        'redirect' => '/api/v2/student/courses',
    ], 410);
});
Route::delete('progress/{id}/{course}', function () {
    return response()->json([
        'error' => 'This endpoint is deprecated. Please use V2 Interactive Course API',
        'redirect' => '/api/v2/student/courses',
    ], 410);
});
Route::post('progress/upsert', function () {
    return response()->json([
        'error' => 'This endpoint is deprecated. Please use V2 Interactive Course API',
        'redirect' => '/api/v2/student/courses',
    ], 410);
});
Route::post('progress/update-certificate', function () {
    return response()->json([
        'error' => 'This endpoint is deprecated. Please use V2 Interactive Course API',
        'redirect' => '/api/v2/student/courses',
    ], 410);
});
Route::get('user-progress', function () {
    return response()->json([
        'error' => 'This endpoint is deprecated. Please use V2 Interactive Course API',
        'redirect' => '/api/v2/student/courses',
    ], 410);
});

// Legacy User Management routes - DEPRECATED
// Please use /api/v2/users/* endpoints instead
Route::middleware(['auth:api', 'role:1'])->group(function () {
    Route::get('users', function () {
        return response()->json([
            'error' => 'This endpoint is deprecated. Please use /api/v2/users',
            'redirect' => '/api/v2/users',
        ], 410);
    });
    Route::post('import-users', function () {
        return response()->json([
            'error' => 'This endpoint is deprecated. Please use /api/v2/users/import',
            'redirect' => '/api/v2/users/import',
        ], 410);
    });
    Route::put('users/{id}/reset-password', function () {
        return response()->json([
            'error' => 'This endpoint is deprecated. Please use /api/v2/users/{id}/reset-password',
            'redirect' => '/api/v2/users/{id}/reset-password',
        ], 410);
    });
    Route::post('users', function () {
        return response()->json([
            'error' => 'This endpoint is deprecated. Please use /api/v2/users',
            'redirect' => '/api/v2/users',
        ], 410);
    });
    Route::put('users/{id}', function () {
        return response()->json([
            'error' => 'This endpoint is deprecated. Please use /api/v2/users/{id}',
            'redirect' => '/api/v2/users/{id}',
        ], 410);
    });
    Route::delete('users/{id}', function () {
        return response()->json([
            'error' => 'This endpoint is deprecated. Please use /api/v2/users/{id}',
            'redirect' => '/api/v2/users/{id}',
        ], 410);
    });
});

Route::middleware(['auth:api'])->group(function () {
    Route::get('expdates', [ExpDateController::class, 'index']);
    Route::get('expdates/{id}', [ExpDateController::class, 'show']);
    Route::put('expdates/curso/{id}', [ExpDateController::class, 'updateByCursoId']);
    Route::post('expdates', [ExpDateController::class, 'store']);
});

// RUTAS DE CURSOS - Legacy (redirigir a V2)
Route::prefix('courses')->group(function () {
    // Rutas públicas - básicas para el frontend legacy
    Route::get('/', function () {
        return response()->json([
            'success' => true,
            'message' => 'Courses API - Please use /api/v2/courses for full functionality',
            'data' => [],
        ]);
    });

    // Redirigir rutas autenticadas
    Route::middleware(['auth:api'])->group(function () {
        // Rutas para estudiantes (rol 2)
        Route::get('/student/my-courses', function () {
            return redirect('/api/v2/student/courses');
        });

        // Rutas para profesores (rol 3)
        Route::get('/teacher/my-courses', function () {
            return redirect('/api/v2/teacher/courses');
        });

        // Rutas generales para todos los roles autenticados
        Route::get('/my-courses', function () {
            $user = auth('api')->user();

            if (! $user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            // Redirigir basado en el rol
            switch ($user->role) {
                case 1: // Admin
                    return redirect('/api/v2/admin/courses');
                case 2: // Estudiante
                    return redirect('/api/v2/student/courses');
                case 3: // Profesor
                    return redirect('/api/v2/teacher/courses');
                default:
                    return response()->json(['error' => 'Invalid user role'], 403);
            }
        });
    });
});
