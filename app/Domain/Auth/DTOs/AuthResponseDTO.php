<?php

namespace App\Domain\Auth\DTOs;

use App\Application\DTOs\BaseDTO;
use App\Models\User;

final class AuthResponseDTO extends BaseDTO
{
    public function __construct(
        public readonly UserResponseDTO $user,
        public readonly string $token,
        public readonly string $tokenType,
        public readonly int $expiresIn,
        public readonly bool $passwordChangeRequired = false,
        public readonly ?string $message = null
    ) {}

    public static function fromUser(User $user, string $token, bool $passwordChangeRequired = false, ?string $message = null): self
    {
        return new self(
            user: UserResponseDTO::fromModel($user),
            token: $token,
            tokenType: 'bearer',
            expiresIn: auth('api')->factory()->getTTL() * 60,
            passwordChangeRequired: $passwordChangeRequired,
            message: $message
        );
    }

    protected function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'tokenType' => ['required', 'string'],
            'expiresIn' => ['required', 'integer', 'min:1'],
            'passwordChangeRequired' => ['boolean'],
            'message' => ['nullable', 'string'],
        ];
    }

    public function toArray(): array
    {
        $data = [
            'access_token' => $this->token,
            'token_type' => $this->tokenType,
            'expires_in' => $this->expiresIn,
            'user' => $this->user->toArray(),
            'password_change_required' => $this->passwordChangeRequired,
        ];

        if ($this->message) {
            $data['message'] = $this->message;
        }

        return $data;
    }

    public function isSuccessful(): bool
    {
        return ! empty($this->token) && ! empty($this->user->id);
    }
}
