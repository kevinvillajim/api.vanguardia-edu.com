<?php

namespace App\Domain\User\Services;

use App\Domain\Auth\DTOs\RegisterDTO;
use App\Domain\User\DTOs\UpdateUserDTO;
use App\Domain\User\Events\UserCreated;
use App\Domain\User\Events\UserDeleted;
use App\Domain\User\Events\UserPasswordReset;
use App\Domain\User\Events\UserUpdated;
use App\Domain\User\Repositories\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UserService
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}

    /**
     * Get user by ID
     */
    public function getUserById(int $id): ?User
    {
        return $this->userRepository->findById($id);
    }

    /**
     * Get user by email
     */
    public function getUserByEmail(string $email): ?User
    {
        return $this->userRepository->findByEmail($email);
    }

    /**
     * Get paginated users with filters
     */
    public function getPaginatedUsers(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        // Sanitize filters
        $sanitizedFilters = $this->sanitizeFilters($filters);

        return $this->userRepository->paginate($perPage, $sanitizedFilters);
    }

    /**
     * Create a new user
     */
    public function createUser(RegisterDTO $dto): User
    {
        try {
            $user = $this->userRepository->create($dto);

            Log::info('User created via admin', [
                'user_id' => $user->id,
                'email' => $user->email,
                'created_by' => auth()->id(),
                'ip' => request()->ip(),
            ]);

            // Dispatch event
            event(new UserCreated($user, auth()->user(), request()->ip()));

            return $user;

        } catch (\Exception $e) {
            Log::error('User creation failed', [
                'email' => $dto->email,
                'error' => $e->getMessage(),
                'created_by' => auth()->id(),
                'ip' => request()->ip(),
            ]);

            throw $e;
        }
    }

    /**
     * Update an existing user
     */
    public function updateUser(int $id, UpdateUserDTO $dto): User
    {
        try {
            $originalUser = $this->userRepository->findById($id);

            if (! $originalUser) {
                throw new \InvalidArgumentException("User with ID {$id} not found");
            }

            $updatedUser = $this->userRepository->update($id, $dto);

            Log::info('User updated', [
                'user_id' => $id,
                'updated_by' => auth()->id(),
                'changes' => $dto->toUpdateData(),
                'ip' => request()->ip(),
            ]);

            // Dispatch event
            event(new UserUpdated($updatedUser, $originalUser, auth()->user(), request()->ip()));

            return $updatedUser;

        } catch (\Exception $e) {
            Log::error('User update failed', [
                'user_id' => $id,
                'error' => $e->getMessage(),
                'updated_by' => auth()->id(),
                'ip' => request()->ip(),
            ]);

            throw $e;
        }
    }

    /**
     * Delete a user
     */
    public function deleteUser(int $id): bool
    {
        try {
            $user = $this->userRepository->findById($id);

            if (! $user) {
                throw new \InvalidArgumentException("User with ID {$id} not found");
            }

            // Prevent deletion of current user
            if ($user->id === auth()->id()) {
                throw new \InvalidArgumentException('Cannot delete your own account');
            }

            // Prevent deletion of other admins (business rule)
            if ($user->role === 1 && auth()->user()->role === 1) {
                throw new \InvalidArgumentException('Cannot delete other admin accounts');
            }

            $success = $this->userRepository->delete($id);

            if ($success) {
                Log::info('User deleted', [
                    'user_id' => $id,
                    'deleted_user_email' => $user->email,
                    'deleted_by' => auth()->id(),
                    'ip' => request()->ip(),
                ]);

                // Dispatch event
                event(new UserDeleted($user, auth()->user(), request()->ip()));
            }

            return $success;

        } catch (\Exception $e) {
            Log::error('User deletion failed', [
                'user_id' => $id,
                'error' => $e->getMessage(),
                'deleted_by' => auth()->id(),
                'ip' => request()->ip(),
            ]);

            throw $e;
        }
    }

    /**
     * Reset user password
     */
    public function resetUserPassword(int $id): string
    {
        try {
            $user = $this->userRepository->findById($id);

            if (! $user) {
                throw new \InvalidArgumentException("User with ID {$id} not found");
            }

            // Generate secure temporary password
            $temporaryPassword = $this->generateTemporaryPassword();

            $success = $this->userRepository->resetPassword($id, $temporaryPassword);

            if (! $success) {
                throw new \RuntimeException("Failed to reset password for user {$id}");
            }

            Log::info('User password reset', [
                'user_id' => $id,
                'reset_by' => auth()->id(),
                'ip' => request()->ip(),
            ]);

            // Dispatch event
            event(new UserPasswordReset($user, auth()->user(), request()->ip()));

            return $temporaryPassword;

        } catch (\Exception $e) {
            Log::error('Password reset failed', [
                'user_id' => $id,
                'error' => $e->getMessage(),
                'reset_by' => auth()->id(),
                'ip' => request()->ip(),
            ]);

            throw $e;
        }
    }

    /**
     * Search users
     */
    public function searchUsers(string $query, int $limit = 10): Collection
    {
        // Sanitize search query
        $sanitizedQuery = trim(strip_tags($query));

        if (strlen($sanitizedQuery) < 2) {
            return new Collection;
        }

        return $this->userRepository->search($sanitizedQuery, $limit);
    }

    /**
     * Get users by role
     */
    public function getUsersByRole(int $role): Collection
    {
        // Validate role
        if (! in_array($role, [1, 2, 3])) {
            throw new \InvalidArgumentException("Invalid role: {$role}");
        }

        return $this->userRepository->getByRole($role);
    }

    /**
     * Get user statistics
     */
    public function getUserStatistics(): array
    {
        return $this->userRepository->getStatistics();
    }

    /**
     * Activate/deactivate user
     */
    public function setUserActive(int $id, bool $active): bool
    {
        try {
            $user = $this->userRepository->findById($id);

            if (! $user) {
                throw new \InvalidArgumentException("User with ID {$id} not found");
            }

            // Prevent deactivation of current user
            if ($user->id === auth()->id() && ! $active) {
                throw new \InvalidArgumentException('Cannot deactivate your own account');
            }

            $success = $this->userRepository->setActive($id, $active);

            if ($success) {
                $action = $active ? 'activated' : 'deactivated';

                Log::info("User {$action}", [
                    'user_id' => $id,
                    'action' => $action,
                    'performed_by' => auth()->id(),
                    'ip' => request()->ip(),
                ]);
            }

            return $success;

        } catch (\Exception $e) {
            Log::error('User activation/deactivation failed', [
                'user_id' => $id,
                'active' => $active,
                'error' => $e->getMessage(),
                'performed_by' => auth()->id(),
                'ip' => request()->ip(),
            ]);

            throw $e;
        }
    }

    /**
     * Import users from array data
     */
    public function importUsers(array $usersData): array
    {
        $results = [
            'success' => [],
            'errors' => [],
            'total' => count($usersData),
        ];

        foreach ($usersData as $index => $userData) {
            try {
                // Add default password for imported users
                $userData['password'] = $userData['password'] ?? $this->generateTemporaryPassword();
                $userData['password_confirmation'] = $userData['password'];

                $dto = RegisterDTO::fromRequest($userData);
                $user = $this->createUser($dto);

                $results['success'][] = [
                    'row' => $index + 1,
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'password' => $userData['password'], // Return temporary password
                ];

            } catch (\Exception $e) {
                $results['errors'][] = [
                    'row' => $index + 1,
                    'email' => $userData['email'] ?? 'Unknown',
                    'error' => $e->getMessage(),
                ];
            }
        }

        Log::info('User import completed', [
            'total' => $results['total'],
            'success' => count($results['success']),
            'errors' => count($results['errors']),
            'imported_by' => auth()->id(),
            'ip' => request()->ip(),
        ]);

        return $results;
    }

    /**
     * Generate secure temporary password
     */
    private function generateTemporaryPassword(): string
    {
        return Str::random(12).rand(10, 99).'!';
    }

    /**
     * Sanitize filters for user queries
     */
    private function sanitizeFilters(array $filters): array
    {
        $sanitized = [];

        if (isset($filters['role']) && in_array((int) $filters['role'], [1, 2, 3])) {
            $sanitized['role'] = (int) $filters['role'];
        }

        if (isset($filters['active']) && in_array((int) $filters['active'], [0, 1])) {
            $sanitized['active'] = (int) $filters['active'];
        }

        if (isset($filters['search']) && is_string($filters['search'])) {
            $sanitized['search'] = trim(strip_tags($filters['search']));
        }

        if (isset($filters['created_from']) && strtotime($filters['created_from'])) {
            $sanitized['created_from'] = $filters['created_from'];
        }

        if (isset($filters['created_to']) && strtotime($filters['created_to'])) {
            $sanitized['created_to'] = $filters['created_to'];
        }

        return $sanitized;
    }
}
