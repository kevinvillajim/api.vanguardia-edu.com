<?php

namespace App\Domain\Auth\DTOs;

use App\Application\DTOs\BaseDTO;

final class LoginDTO extends BaseDTO
{
    public function __construct(
        public readonly string $email,
        public readonly string $password,
        public readonly bool $remember = false,
        public readonly ?string $ip = null,
        public readonly ?string $userAgent = null
    ) {
        $this->validate();
    }

    public static function fromRequest(array $data): self
    {
        return new self(
            email: trim(strtolower($data['email'] ?? '')),
            password: $data['password'] ?? '',
            remember: (bool) ($data['remember'] ?? false),
            ip: request()->ip(),
            userAgent: request()->userAgent()
        );
    }

    protected function rules(): array
    {
        return [
            'email' => ['required', 'email:rfc', 'max:255'],
            'password' => ['required', 'string', 'min:1'],
            'remember' => ['boolean'],
            'ip' => ['nullable', 'ip'],
            'userAgent' => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function messages(): array
    {
        return [
            'email.required' => 'El email es obligatorio',
            'email.email' => 'El email debe tener un formato válido',
            'password.required' => 'La contraseña es obligatoria',
            'ip.ip' => 'La dirección IP no es válida',
        ];
    }

    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'password' => $this->password,
            'remember' => $this->remember,
            'ip' => $this->ip,
            'user_agent' => $this->userAgent,
        ];
    }
}
