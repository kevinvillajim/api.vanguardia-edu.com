<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Auth\DTOs\RegisterDTO;
use App\Domain\User\DTOs\UpdateUserDTO;
use App\Domain\User\Repositories\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EloquentUserRepository implements UserRepositoryInterface
{
    public function __construct(private User $model) {}

    public function findById(int $id): ?User
    {
        return $this->model->find($id);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->model->where('email', $email)->first();
    }

    public function findByCi(string $ci): ?User
    {
        return $this->model->where('ci', $ci)->first();
    }

    public function create(RegisterDTO $dto): User
    {
        return DB::transaction(function () use ($dto) {
            return $this->model->create($dto->toUserData());
        });
    }

    public function update(int $id, UpdateUserDTO $dto): User
    {
        return DB::transaction(function () use ($id, $dto) {
            $user = $this->findById($id);

            if (! $user) {
                throw new \InvalidArgumentException("User with ID {$id} not found");
            }

            $updateData = $dto->toUpdateData();

            if (! empty($updateData)) {
                $user->update($updateData);
                $user->refresh();
            }

            return $user;
        });
    }

    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $user = $this->findById($id);

            if (! $user) {
                return false;
            }

            // Soft delete or hard delete based on business rules
            return $user->delete();
        });
    }

    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        // Apply filters
        if (! empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (! empty($filters['active'])) {
            $query->where('active', $filters['active']);
        }

        if (! empty($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('email', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('ci', 'LIKE', "%{$searchTerm}%");
            });
        }

        if (! empty($filters['created_from'])) {
            $query->where('created_at', '>=', $filters['created_from']);
        }

        if (! empty($filters['created_to'])) {
            $query->where('created_at', '<=', $filters['created_to']);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function all(): Collection
    {
        return $this->model->orderBy('name')->get();
    }

    public function getByRole(int $role): Collection
    {
        return $this->model->where('role', $role)
            ->orderBy('name')
            ->get();
    }

    public function getActive(): Collection
    {
        return $this->model->where('active', 1)
            ->orderBy('name')
            ->get();
    }

    public function search(string $query, int $limit = 10): Collection
    {
        return $this->model->where(function ($q) use ($query) {
            $q->where('name', 'LIKE', "%{$query}%")
                ->orWhere('email', 'LIKE', "%{$query}%")
                ->orWhere('ci', 'LIKE', "%{$query}%");
        })
            ->limit($limit)
            ->orderBy('name')
            ->get();
    }

    public function resetPassword(int $id, string $newPassword): bool
    {
        return DB::transaction(function () use ($id, $newPassword) {
            $user = $this->findById($id);

            if (! $user) {
                return false;
            }

            return $user->update([
                'password' => Hash::make($newPassword),
                'password_changed' => false, // Require password change on next login
                'updated_at' => now(),
            ]);
        });
    }

    public function markPasswordChangeRequired(int $id): bool
    {
        $user = $this->findById($id);

        if (! $user) {
            return false;
        }

        return $user->update(['password_changed' => false]);
    }

    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $query = $this->model->where('email', $email);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function ciExists(string $ci, ?int $excludeId = null): bool
    {
        $query = $this->model->where('ci', $ci);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function setActive(int $id, bool $active): bool
    {
        $user = $this->findById($id);

        if (! $user) {
            return false;
        }

        return $user->update(['active' => $active]);
    }

    public function withProgress(): Collection
    {
        return $this->model->with(['progress.course'])
            ->orderBy('name')
            ->get();
    }

    public function getStatistics(): array
    {
        return [
            'total_users' => $this->model->count(),
            'active_users' => $this->model->where('active', 1)->count(),
            'inactive_users' => $this->model->where('active', 0)->count(),
            'admins' => $this->model->where('role', 1)->count(),
            'students' => $this->model->where('role', 2)->count(),
            'teachers' => $this->model->where('role', 3)->count(),
            'users_requiring_password_change' => $this->model->where('password_changed', 0)->count(),
            'recent_registrations' => $this->model->where('created_at', '>=', now()->subDays(30))->count(),
        ];
    }
}
