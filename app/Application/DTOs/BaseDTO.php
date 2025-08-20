<?php

namespace App\Application\DTOs;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

abstract class BaseDTO implements Arrayable
{
    /**
     * Validate the DTO data
     *
     * @throws ValidationException
     */
    protected function validate(): void
    {
        $validator = Validator::make(
            $this->toArray(),
            $this->rules(),
            $this->messages(),
            $this->attributes()
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Get validation rules for the DTO
     */
    abstract protected function rules(): array;

    /**
     * Get custom validation messages
     */
    protected function messages(): array
    {
        return [];
    }

    /**
     * Get custom attribute names for validation
     */
    protected function attributes(): array
    {
        return [];
    }

    /**
     * Convert the DTO to an array
     */
    abstract public function toArray(): array;

    /**
     * Convert the DTO to JSON
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }

    /**
     * Get only the specified keys from the DTO
     */
    public function only(array $keys): array
    {
        return array_intersect_key($this->toArray(), array_flip($keys));
    }

    /**
     * Get all keys except the specified ones from the DTO
     */
    public function except(array $keys): array
    {
        return array_diff_key($this->toArray(), array_flip($keys));
    }
}
