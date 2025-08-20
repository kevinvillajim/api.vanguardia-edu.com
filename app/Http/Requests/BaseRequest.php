<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;

abstract class BaseRequest extends FormRequest
{
    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator): void
    {
        $errors = $validator->errors()->toArray();

        // Log validation failures for security monitoring
        $this->logValidationFailure($errors);

        // Create standardized error response
        $response = response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $errors,
            'error_code' => 'validation_failed',
            'timestamp' => now()->toISOString(),
        ], 422);

        throw new HttpResponseException($response);
    }

    /**
     * Log validation failures for security monitoring
     */
    private function logValidationFailure(array $errors): void
    {
        // Don't log sensitive data like passwords
        $safeInput = $this->getSafeInputForLogging();

        Log::info('Form validation failed', [
            'request_class' => get_class($this),
            'endpoint' => $this->path(),
            'method' => $this->method(),
            'ip' => $this->ip(),
            'user_agent' => $this->userAgent(),
            'user_id' => auth()->id(),
            'errors' => array_keys($errors), // Only log field names, not values
            'input_fields' => array_keys($safeInput),
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Get safe input data for logging (exclude sensitive fields)
     */
    protected function getSafeInputForLogging(): array
    {
        $input = $this->all();

        // Remove sensitive fields
        $sensitiveFields = [
            'password',
            'password_confirmation',
            'current_password',
            'new_password',
            'token',
            'api_key',
            'secret',
            'private_key',
        ];

        foreach ($sensitiveFields as $field) {
            if (isset($input[$field])) {
                $input[$field] = '[REDACTED]';
            }
        }

        return $input;
    }

    /**
     * Common validation rules that can be reused
     */
    protected function getCommonRules(): array
    {
        return [
            'password_strong' => [
                'string',
                'min:8',
                'max:128',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/',
            ],
            'email_secure' => [
                'email:rfc,dns',
                'max:255',
                'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
            ],
            'name_safe' => [
                'string',
                'min:2',
                'max:255',
                'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/',
            ],
            'ci_ecuadorian' => [
                'string',
                'min:10',
                'max:13',
                'regex:/^[0-9]+$/',
            ],
            'phone_international' => [
                'string',
                'min:10',
                'max:15',
                'regex:/^[0-9+\-\s]+$/',
            ],
        ];
    }

    /**
     * Get common error messages
     */
    protected function getCommonMessages(): array
    {
        return [
            'password_strong.regex' => 'La contraseña debe contener al menos: una mayúscula, una minúscula, un número y un carácter especial',
            'email_secure.email' => 'El email debe tener un formato válido',
            'email_secure.regex' => 'El email contiene caracteres no válidos',
            'name_safe.regex' => 'El nombre solo puede contener letras y espacios',
            'ci_ecuadorian.regex' => 'La cédula debe contener solo números',
            'phone_international.regex' => 'El teléfono debe contener solo números, espacios, + y -',
        ];
    }

    /**
     * Check if request contains potentially malicious content
     */
    protected function containsMaliciousContent(string $input): bool
    {
        $maliciousPatterns = [
            // XSS patterns
            '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi',
            '/javascript:/i',
            '/vbscript:/i',
            '/on\w+\s*=/i',

            // SQL injection patterns
            '/(\b(select|insert|update|delete|drop|create|alter|exec|execute)\b)/i',
            '/(\b(union|or|and)\b.*\b(select|insert|update|delete)\b)/i',

            // File inclusion patterns
            '/\.\.\//i',
            '/\0/i',

            // Command injection patterns
            '/[;&|`$]/i',
        ];

        foreach ($maliciousPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sanitize input to remove potentially dangerous content
     */
    protected function sanitizeInput(array $input): array
    {
        array_walk_recursive($input, function (&$value) {
            if (is_string($value)) {
                // Remove null bytes
                $value = str_replace("\0", '', $value);

                // Trim whitespace
                $value = trim($value);

                // Remove control characters except tabs, newlines, and carriage returns
                $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);
            }
        });

        return $input;
    }
}
