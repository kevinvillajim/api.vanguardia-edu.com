<?php

namespace App\Domain\User\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserPasswordReset
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly ?User $resetBy,
        public readonly ?string $ip,
        public readonly \DateTime $timestamp = new \DateTime
    ) {}
}
