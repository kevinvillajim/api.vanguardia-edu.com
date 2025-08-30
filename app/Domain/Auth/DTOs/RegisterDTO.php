<?php

namespace App\Domain\Auth\DTOs;

use App\Application\DTOs\BaseDTO;

final class RegisterDTO extends BaseDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
        public readonly string $ci,
        public readonly ?string $phone = null,
        public readonly ?string $bio = null,
        public readonly ?string $ip = null,
        public readonly ?string $userAgent = null
    ) {
        $this->validate();
    }

    public static function fromRequest(array $data): self
    {
        return new self(
            name: trim($data['name'] ?? ''),
            email: trim(strtolower($data['email'] ?? '')),
            password: $data['password'] ?? '',
            ci: trim($data['ci'] ?? ''),
            phone: ! empty($data['phone']) ? trim($data['phone']) : null,
            bio: ! empty($data['bio']) ? trim($data['bio']) : null,
            ip: request()->ip(),
            userAgent: request()->userAgent()
        );
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/'],
            'email' => ['required', 'email:rfc,dns', 'max:255', 'unique:users,email'],
            'password' => [
                'required',
                'string',
                'min:8',
                'max:128',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/',
                'confirmed',
            ],
            'ci' => ['required', 'string', 'min:10', 'max:13', 'regex:/^[0-9]+$/', 'unique:users,ci'],
            'phone' => ['nullable', 'string', 'min:10', 'max:15', 'regex:/^[0-9+\-\s]+$/'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'ip' => ['nullable', 'ip'],
            'userAgent' => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio',
            'name.regex' => 'El nombre solo puede contener letras y espacios',
            'email.required' => 'El email es obligatorio',
            'email.email' => 'El email debe tener un formato válido',
            'email.unique' => 'Este email ya está registrado',
            'password.required' => 'La contraseña es obligatoria',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres',
            'password.regex' => 'La contraseña debe contener al menos: una mayúscula, una minúscula, un número y un carácter especial',
            'password.confirmed' => 'La confirmación de contraseña no coincide',
            'ci.required' => 'La cédula es obligatoria',
            'ci.regex' => 'La cédula debe contener solo números',
            'ci.unique' => 'Esta cédula ya está registrada',
            'phone.regex' => 'El teléfono debe contener solo números, espacios, + y -',
        ];
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'ci' => $this->ci,
            'phone' => $this->phone,
            'bio' => $this->bio,
            'ip' => $this->ip,
            'user_agent' => $this->userAgent,
        ];
    }

    public function toUserData(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'password' => bcrypt($this->password),
            'ci' => $this->ci,
            'phone' => $this->phone,
            'bio' => $this->bio,
            'role' => 2, // Default student role
            'active' => 1,
            'password_changed' => 1, // New registrations have changed password
        ];
    }
}
