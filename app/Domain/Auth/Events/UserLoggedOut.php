<?php

namespace App\Domain\Auth\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserLoggedOut
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly ?string $ip,
        public readonly ?string $userAgent,
        public readonly \DateTime $timestamp = new \DateTime
    ) {}
}
