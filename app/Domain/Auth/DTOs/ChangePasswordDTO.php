<?php

namespace App\Domain\Auth\DTOs;

use App\Application\DTOs\BaseDTO;

final class ChangePasswordDTO extends BaseDTO
{
    public function __construct(
        public readonly string $password,
        public readonly string $passwordConfirmation,
        public readonly ?string $currentPassword,
        public readonly int $userId,
        public readonly ?string $ip = null,
        public readonly ?string $userAgent = null
    ) {
        $this->validate();
    }

    public static function fromRequest(array $data, int $userId): self
    {
        return new self(
            password: $data['password'] ?? '',
            passwordConfirmation: $data['password_confirmation'] ?? '',
            currentPassword: $data['current_password'] ?? null,
            userId: $userId,
            ip: request()->ip(),
            userAgent: request()->userAgent()
        );
    }

    protected function rules(): array
    {
        return [
            'password' => [
                'required',
                'string',
                'min:8',
                'max:128',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/',
                'different:currentPassword',
            ],
            'passwordConfirmation' => ['required', 'same:password'],
            'currentPassword' => ['nullable', 'string'],
            'userId' => ['required', 'integer', 'min:1'],
            'ip' => ['nullable', 'ip'],
            'userAgent' => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function messages(): array
    {
        return [
            'password.required' => 'La nueva contraseña es obligatoria',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres',
            'password.regex' => 'La contraseña debe contener al menos: una mayúscula, una minúscula, un número y un carácter especial',
            'password.different' => 'La nueva contraseña debe ser diferente a la actual',
            'passwordConfirmation.required' => 'La confirmación de contraseña es obligatoria',
            'passwordConfirmation.same' => 'La confirmación de contraseña no coincide',
        ];
    }

    public function toArray(): array
    {
        return [
            'password' => $this->password,
            'passwordConfirmation' => $this->passwordConfirmation,
            'currentPassword' => $this->currentPassword,
            'userId' => $this->userId,
            'ip' => $this->ip,
            'userAgent' => $this->userAgent,
        ];
    }

    public function getHashedPassword(): string
    {
        return bcrypt($this->password);
    }

    public function requiresCurrentPassword(): bool
    {
        return ! is_null($this->currentPassword);
    }
}
