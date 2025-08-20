<?php

namespace App\Providers;

use App\Http\Middleware\RoleMiddleware;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Original API routes
        Route::middleware('api')
            ->prefix('api')
            ->group(base_path('routes/api.php'));

        // New Clean Architecture API routes (v2)
        Route::middleware('api')
            ->prefix('api/v2')
            ->group(base_path('routes/api_v2.php'));

        // Register role middleware for API v2
        Route::aliasMiddleware('role', RoleMiddleware::class);
    }
}
