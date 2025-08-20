<?php

namespace App\Domain\User\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly User $originalUser,
        public readonly ?User $updatedBy,
        public readonly ?string $ip,
        public readonly \DateTime $timestamp = new \DateTime
    ) {}
}
