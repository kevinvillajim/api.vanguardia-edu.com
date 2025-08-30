<?php

namespace App\Infrastructure\Providers;

use App\Domain\User\Repositories\UserRepositoryInterface;
use App\Infrastructure\Repositories\EloquentUserRepository;
use App\Domain\Course\Repositories\CourseRepositoryInterface;
use App\Infrastructure\Course\Repositories\EloquentCourseRepository;
use App\Domain\Course\Services\CourseManagementService;
use App\Domain\Course\Factories\ComponentFactory;
use App\Domain\Student\Services\StudentProgressService;
use App\Domain\Admin\Services\AdminService;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Repository bindings
     */
    public array $bindings = [
        UserRepositoryInterface::class => EloquentUserRepository::class,
        CourseRepositoryInterface::class => EloquentCourseRepository::class,
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

        // Course services
        $this->app->singleton(ComponentFactory::class);
        
        $this->app->singleton(CourseManagementService::class, function ($app) {
            return new CourseManagementService(
                $app->make(CourseRepositoryInterface::class),
                $app->make(ComponentFactory::class)
            );
        });

        // Student services
        $this->app->singleton(StudentProgressService::class);

        // Admin services
        $this->app->singleton(AdminService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
