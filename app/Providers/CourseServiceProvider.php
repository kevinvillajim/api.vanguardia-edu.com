<?php

namespace App\Providers;

use App\Domain\Course\Repositories\CourseRepositoryInterface;
use App\Infrastructure\Course\Repositories\EloquentCourseRepository;
use Illuminate\Support\ServiceProvider;

class CourseServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind repository interface to implementation
        $this->app->bind(
            CourseRepositoryInterface::class,
            EloquentCourseRepository::class
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
