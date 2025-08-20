<?php

namespace App\Infrastructure\Providers;

use App\Domain\User\Repositories\UserRepositoryInterface;
use App\Infrastructure\Repositories\EloquentUserRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Repository bindings
     */
    public array $bindings = [
        UserRepositoryInterface::class => EloquentUserRepository::class,
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        // Repositories are automatically bound via $bindings property

        // Additional singleton bindings if needed
        $this->app->singleton(UserRepositoryInterface::class, function ($app) {
            return new EloquentUserRepository($app->make(\App\Models\User::class));
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
