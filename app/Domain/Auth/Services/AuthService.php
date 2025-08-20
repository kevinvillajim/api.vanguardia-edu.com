<?php

namespace App\Domain\Auth\Services;

use App\Domain\Auth\DTOs\AuthResponseDTO;
use App\Domain\Auth\DTOs\ChangePasswordDTO;
use App\Domain\Auth\DTOs\LoginDTO;
use App\Domain\Auth\DTOs\RegisterDTO;
use App\Domain\Auth\Events\PasswordChanged;
use App\Domain\Auth\Events\UserLoggedIn;
use App\Domain\Auth\Events\UserLoggedOut;
use App\Domain\Auth\Events\UserRegistered;
use App\Domain\User\Repositories\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthService
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}

    /**
     * Authenticate user with credentials
     */
    public function login(LoginDTO $dto): AuthResponseDTO
    {
        // Rate limiting by IP
        $this->checkRateLimit($dto->ip, 'login');

        try {
            // Find user
            $user = $this->userRepository->findByEmail($dto->email);

            if (! $user || ! Hash::check($dto->password, $user->password)) {
                $this->incrementRateLimit($dto->ip, 'login');

                Log::warning('Failed login attempt', [
                    'email' => $dto->email,
                    'ip' => $dto->ip,
                    'user_agent' => $dto->userAgent,
                ]);

                throw new AuthenticationException('Invalid credentials');
            }

            // Check if user is active
            if (! $user->active) {
                Log::warning('Inactive user login attempt', [
                    'user_id' => $user->id,
                    'email' => $dto->email,
                    'ip' => $dto->ip,
                ]);

                throw new AuthenticationException('Account is deactivated');
            }

            // Generate JWT token
            $token = JWTAuth::fromUser($user);

            if (! $token) {
                throw new AuthenticationException('Could not create token');
            }

            // Check if password change is required
            $passwordChangeRequired = ! $user->password_changed;

            // Clear rate limiting on successful login
            RateLimiter::clear($this->getRateLimitKey($dto->ip, 'login'));

            // Log successful login
            Log::info('User logged in successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $dto->ip,
                'password_change_required' => $passwordChangeRequired,
            ]);

            // Dispatch event
            event(new UserLoggedIn($user, $dto->ip, $dto->userAgent));

            return AuthResponseDTO::fromUser(
                $user,
                $token,
                $passwordChangeRequired,
                $passwordChangeRequired ? 'Password change required' : null
            );

        } catch (AuthenticationException $e) {
            $this->incrementRateLimit($dto->ip, 'login');
            throw $e;
        }
    }

    /**
     * Register a new user
     */
    public function register(RegisterDTO $dto): AuthResponseDTO
    {
        // Rate limiting by IP
        $this->checkRateLimit($dto->ip, 'register');

        try {
            // Create user
            $user = $this->userRepository->create($dto);

            // Generate JWT token
            $token = JWTAuth::fromUser($user);

            if (! $token) {
                throw new \RuntimeException('Could not create token for new user');
            }

            // Log registration
            Log::info('User registered successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $dto->ip,
            ]);

            // Dispatch event
            event(new UserRegistered($user, $dto->ip, $dto->userAgent));

            return AuthResponseDTO::fromUser($user, $token, false, 'Registration successful');

        } catch (\Exception $e) {
            $this->incrementRateLimit($dto->ip, 'register');

            Log::error('User registration failed', [
                'email' => $dto->email,
                'error' => $e->getMessage(),
                'ip' => $dto->ip,
            ]);

            throw $e;
        }
    }

    /**
     * Change user password
     */
    public function changePassword(ChangePasswordDTO $dto): bool
    {
        try {
            $user = $this->userRepository->findById($dto->userId);

            if (! $user) {
                throw new \InvalidArgumentException('User not found');
            }

            // If current password is provided, verify it
            if ($dto->requiresCurrentPassword()) {
                if (! Hash::check($dto->currentPassword, $user->password)) {
                    throw new AuthenticationException('Current password is incorrect');
                }
            }

            // Update password
            $success = $user->update([
                'password' => $dto->getHashedPassword(),
                'password_changed' => true,
                'updated_at' => now(),
            ]);

            if ($success) {
                Log::info('Password changed successfully', [
                    'user_id' => $user->id,
                    'ip' => $dto->ip,
                ]);

                // Dispatch event
                event(new PasswordChanged($user, $dto->ip, $dto->userAgent));
            }

            return $success;

        } catch (\Exception $e) {
            Log::error('Password change failed', [
                'user_id' => $dto->userId,
                'error' => $e->getMessage(),
                'ip' => $dto->ip,
            ]);

            throw $e;
        }
    }

    /**
     * Get current authenticated user
     */
    public function getCurrentUser(): ?User
    {
        return Auth::guard('api')->user();
    }

    /**
     * Logout current user
     */
    public function logout(): bool
    {
        try {
            $user = $this->getCurrentUser();

            // Invalidate JWT token
            JWTAuth::invalidate(JWTAuth::getToken());

            // Log logout
            if ($user) {
                Log::info('User logged out', [
                    'user_id' => $user->id,
                    'ip' => request()->ip(),
                ]);

                // Dispatch event
                event(new UserLoggedOut($user, request()->ip(), request()->userAgent()));
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Logout failed', [
                'error' => $e->getMessage(),
                'ip' => request()->ip(),
            ]);

            // Even if there's an error, we should consider logout successful
            // since the token might be invalid anyway
            return true;
        }
    }

    /**
     * Refresh JWT token
     */
    public function refreshToken(): string
    {
        try {
            $token = JWTAuth::refresh(JWTAuth::getToken());

            Log::info('Token refreshed', [
                'user_id' => $this->getCurrentUser()?->id,
                'ip' => request()->ip(),
            ]);

            return $token;

        } catch (\Exception $e) {
            Log::error('Token refresh failed', [
                'error' => $e->getMessage(),
                'ip' => request()->ip(),
            ]);

            throw new AuthenticationException('Could not refresh token');
        }
    }

    /**
     * Check rate limiting
     */
    private function checkRateLimit(string $ip, string $action): void
    {
        $key = $this->getRateLimitKey($ip, $action);
        $maxAttempts = $this->getMaxAttempts($action);
        $decayMinutes = $this->getDecayMinutes($action);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);

            Log::warning('Rate limit exceeded', [
                'ip' => $ip,
                'action' => $action,
                'retry_after_seconds' => $seconds,
            ]);

            throw ValidationException::withMessages([
                'rate_limit' => "Too many {$action} attempts. Try again in {$seconds} seconds.",
            ]);
        }
    }

    /**
     * Increment rate limit counter
     */
    private function incrementRateLimit(string $ip, string $action): void
    {
        $key = $this->getRateLimitKey($ip, $action);
        $decayMinutes = $this->getDecayMinutes($action);

        RateLimiter::hit($key, $decayMinutes * 60);
    }

    /**
     * Get rate limit key
     */
    private function getRateLimitKey(string $ip, string $action): string
    {
        return "auth:{$action}:{$ip}";
    }

    /**
     * Get max attempts for action
     */
    private function getMaxAttempts(string $action): int
    {
        return match ($action) {
            'login' => 5,
            'register' => 3,
            default => 5
        };
    }

    /**
     * Get decay minutes for action
     */
    private function getDecayMinutes(string $action): int
    {
        return match ($action) {
            'login' => 15,
            'register' => 60,
            default => 15
        };
    }
}
