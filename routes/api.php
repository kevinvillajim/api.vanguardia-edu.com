<?php

use App\Http\Controllers\UserImportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProgressController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ExpDateController;
use App\Models\User;


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
    if (!$user) {
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

Route::group(['prefix' => 'auth'], function ($router) {

    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
});

Route::middleware(['auth:api'])->group(function () {
    Route::post('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::post('edit-profile', [AuthController::class, 'updateProfile']);
    Route::post('auth/change-password', [AuthController::class, 'changePassword']);
});

Route::middleware(['auth:api'])->group(function () {
    Route::get('progress', [ProgressController::class, 'index']);
    Route::post('progress', [ProgressController::class, 'store']);
    Route::get('progress/{id}', [ProgressController::class, 'show']);
    Route::put('progress/{id}', [ProgressController::class, 'update']);
    Route::get('progress/student/{id}', [ProgressController::class, 'showProgress']);
    Route::get('progress/{id}/{course}', [ProgressController::class, 'showProgress']);
    Route::delete('progress/{id}/{course}', [ProgressController::class, 'destroy']);
    Route::post('progress/upsert', [ProgressController::class, 'upsert']);
    Route::post('progress/update-certificate', [ProgressController::class, 'updateCertificate']);
    Route::get('user-progress', [ProgressController::class, 'getUserProgress']);
});

Route::middleware(['auth:api', 'role:1'])->group(function () {
    Route::get('users', function () {
        return User::all();
    });
    Route::post('import-users', [UserImportController::class, 'import']);
    Route::put('users/{id}/reset-password', [UserController::class, 'resetPassword']);
    Route::post('users', [UserController::class, 'store']);
    Route::put('users/{id}', [UserController::class, 'update']);
    Route::delete('users/{id}', [UserController::class, 'destroy']);
});

Route::middleware(['auth:api'])->group(function () {
    Route::get('expdates', [ExpDateController::class, 'index']);
    Route::get('expdates/{id}', [ExpDateController::class, 'show']);
    Route::put('expdates/curso/{id}', [ExpDateController::class, 'updateByCursoId']);
    Route::post('expdates', [ExpDateController::class, 'store']);
});
