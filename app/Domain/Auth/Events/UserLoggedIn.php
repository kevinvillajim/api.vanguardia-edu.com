<?php

namespace App\Domain\Auth\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserLoggedIn
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly ?string $ip,
        public readonly ?string $userAgent,
        public readonly \DateTime $timestamp = new \DateTime
    ) {}

    /**
     * Get the user's role name
     */
    public function getRoleName(): string
    {
        return match ((int) $this->user->role) {
            1 => 'admin',
            2 => 'student',
            3 => 'teacher',
            default => 'unknown'
        };
    }

    /**
     * Check if this is a suspicious login
     */
    public function isSuspicious(): bool
    {
        // Add logic to detect suspicious logins
        // e.g., login from new location, unusual time, etc.
        return false;
    }
}
