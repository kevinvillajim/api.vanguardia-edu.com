<?php

namespace App\Providers;

use App\Domain\Auth\Events\PasswordChanged;
use App\Domain\Auth\Events\UserLoggedIn;
use App\Domain\Auth\Events\UserLoggedOut;
use App\Domain\Auth\Events\UserRegistered;
use App\Domain\User\Events\UserCreated;
use App\Domain\User\Events\UserDeleted;
use App\Domain\User\Events\UserPasswordReset;
use App\Domain\User\Events\UserUpdated;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        // Laravel default events
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        parent::boot();
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
