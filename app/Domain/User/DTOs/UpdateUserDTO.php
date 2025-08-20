<?php

namespace App\Domain\User\DTOs;

use App\Application\DTOs\BaseDTO;

final class UpdateUserDTO extends BaseDTO
{
    public function __construct(
        public readonly ?string $name,
        public readonly ?string $email,
        public readonly ?string $ci,
        public readonly ?string $phone,
        public readonly ?string $bio,
        public readonly ?string $avatar,
        public readonly ?int $role,
        public readonly ?bool $active,
        public readonly ?string $password,
        public readonly int $userId
    ) {
        $this->validate();
    }

    public static function fromRequest(array $data, int $userId): self
    {
        return new self(
            name: ! empty($data['name']) ? trim($data['name']) : null,
            email: ! empty($data['email']) ? trim(strtolower($data['email'])) : null,
            ci: ! empty($data['ci']) ? trim($data['ci']) : null,
            phone: ! empty($data['phone']) ? trim($data['phone']) : null,
            bio: array_key_exists('bio', $data) ? trim($data['bio']) : null,
            avatar: array_key_exists('avatar', $data) ? $data['avatar'] : null,
            role: array_key_exists('role', $data) ? (int) $data['role'] : null,
            active: array_key_exists('active', $data) ? (bool) $data['active'] : null,
            password: ! empty($data['password']) ? $data['password'] : null,
            userId: $userId
        );
    }

    protected function rules(): array
    {
        $rules = [
            'userId' => ['required', 'integer', 'min:1'],
        ];

        if ($this->name !== null) {
            $rules['name'] = ['string', 'min:2', 'max:255', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/'];
        }

        if ($this->email !== null) {
            $rules['email'] = ['email:rfc,dns', 'max:255', "unique:users,email,{$this->userId}"];
        }

        if ($this->ci !== null) {
            $rules['ci'] = ['string', 'min:10', 'max:13', 'regex:/^[0-9]+$/', "unique:users,ci,{$this->userId}"];
        }

        if ($this->phone !== null) {
            $rules['phone'] = ['string', 'min:10', 'max:15', 'regex:/^[0-9+\-\s]+$/'];
        }

        if ($this->bio !== null) {
            $rules['bio'] = ['string', 'max:1000'];
        }

        if ($this->avatar !== null) {
            $rules['avatar'] = ['string', 'max:255'];
        }

        if ($this->role !== null) {
            $rules['role'] = ['integer', 'in:1,2,3'];
        }

        if ($this->active !== null) {
            $rules['active'] = ['boolean'];
        }

        if ($this->password !== null) {
            $rules['password'] = [
                'string',
                'min:8',
                'max:128',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/',
            ];
        }

        return $rules;
    }

    protected function messages(): array
    {
        return [
            'name.regex' => 'El nombre solo puede contener letras y espacios',
            'email.email' => 'El email debe tener un formato válido',
            'email.unique' => 'Este email ya está registrado',
            'ci.regex' => 'La cédula debe contener solo números',
            'ci.unique' => 'Esta cédula ya está registrada',
            'phone.regex' => 'El teléfono debe contener solo números, espacios, + y -',
            'role.in' => 'El rol debe ser: 1 (Admin), 2 (Estudiante) o 3 (Profesor)',
            'password.regex' => 'La contraseña debe contener al menos: una mayúscula, una minúscula, un número y un carácter especial',
        ];
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'email' => $this->email,
            'ci' => $this->ci,
            'phone' => $this->phone,
            'bio' => $this->bio,
            'avatar' => $this->avatar,
            'role' => $this->role,
            'active' => $this->active,
            'password' => $this->password,
            'userId' => $this->userId,
        ], fn ($value) => $value !== null);
    }

    public function toUpdateData(): array
    {
        $data = $this->except(['userId', 'password']);

        if ($this->password !== null) {
            $data['password'] = bcrypt($this->password);
            $data['password_changed'] = true;
        }

        return array_filter($data, fn ($value) => $value !== null);
    }

    public function hasUpdates(): bool
    {
        return count($this->toUpdateData()) > 0;
    }
}
