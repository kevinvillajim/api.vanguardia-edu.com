<?php

namespace App\Domain\Auth\DTOs;

use App\Application\DTOs\BaseDTO;
use App\Models\User;
use Carbon\Carbon;

final class UserResponseDTO extends BaseDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly string $ci,
        public readonly ?string $phone,
        public readonly ?string $bio,
        public readonly ?string $avatar,
        public readonly int $role,
        public readonly int $active,
        public readonly bool $passwordChanged,
        public readonly ?Carbon $emailVerifiedAt,
        public readonly Carbon $createdAt,
        public readonly Carbon $updatedAt
    ) {}

    public static function fromModel(User $user): self
    {
        return new self(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            ci: $user->ci,
            phone: $user->phone,
            bio: $user->bio,
            avatar: $user->avatar,
            role: (int) $user->role,
            active: (int) $user->active,
            passwordChanged: (bool) $user->password_changed,
            emailVerifiedAt: $user->email_verified_at ? Carbon::parse($user->email_verified_at) : null,
            createdAt: Carbon::parse($user->created_at),
            updatedAt: Carbon::parse($user->updated_at)
        );
    }

    protected function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'min:1'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'ci' => ['required', 'string', 'max:13'],
            'phone' => ['nullable', 'string', 'max:15'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'avatar' => ['nullable', 'string'],
            'role' => ['required', 'integer', 'in:1,2,3'], // 1=admin, 2=student, 3=teacher
            'active' => ['required', 'integer', 'in:0,1'],
            'passwordChanged' => ['required', 'boolean'],
        ];
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'ci' => $this->ci,
            'phone' => $this->phone,
            'bio' => $this->bio,
            'avatar' => $this->avatar,
            'role' => $this->role,
            'active' => $this->active,
            'password_changed' => $this->passwordChanged,
            'email_verified_at' => $this->emailVerifiedAt?->toISOString(),
            'created_at' => $this->createdAt->toISOString(),
            'updated_at' => $this->updatedAt->toISOString(),
        ];
    }

    public function toPublicArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'avatar' => $this->avatar,
            'role' => $this->role,
            'created_at' => $this->createdAt->toISOString(),
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 1;
    }

    public function isStudent(): bool
    {
        return $this->role === 2;
    }

    public function isTeacher(): bool
    {
        return $this->role === 3;
    }

    public function isActive(): bool
    {
        return $this->active === 1;
    }
}
