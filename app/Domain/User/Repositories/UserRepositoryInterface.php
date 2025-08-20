<?php

namespace App\Domain\User\Repositories;

use App\Domain\Auth\DTOs\RegisterDTO;
use App\Domain\User\DTOs\UpdateUserDTO;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface UserRepositoryInterface
{
    /**
     * Find user by ID
     */
    public function findById(int $id): ?User;

    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?User;

    /**
     * Find user by CI (cedula)
     */
    public function findByCi(string $ci): ?User;

    /**
     * Create a new user
     */
    public function create(RegisterDTO $dto): User;

    /**
     * Update an existing user
     */
    public function update(int $id, UpdateUserDTO $dto): User;

    /**
     * Delete a user
     */
    public function delete(int $id): bool;

    /**
     * Get paginated list of users
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator;

    /**
     * Get all users
     */
    public function all(): Collection;

    /**
     * Get users by role
     */
    public function getByRole(int $role): Collection;

    /**
     * Get active users
     */
    public function getActive(): Collection;

    /**
     * Search users by name or email
     */
    public function search(string $query, int $limit = 10): Collection;

    /**
     * Reset user password
     */
    public function resetPassword(int $id, string $newPassword): bool;

    /**
     * Mark user as requiring password change
     */
    public function markPasswordChangeRequired(int $id): bool;

    /**
     * Check if email exists
     */
    public function emailExists(string $email, ?int $excludeId = null): bool;

    /**
     * Check if CI exists
     */
    public function ciExists(string $ci, ?int $excludeId = null): bool;

    /**
     * Activate/deactivate user
     */
    public function setActive(int $id, bool $active): bool;

    /**
     * Get users with their progress
     */
    public function withProgress(): Collection;

    /**
     * Get user statistics
     */
    public function getStatistics(): array;
}
