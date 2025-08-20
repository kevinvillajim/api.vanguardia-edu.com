<?php

namespace App\Http\Controllers;

use App\Domain\Auth\DTOs\RegisterDTO;
use App\Domain\Auth\DTOs\UserResponseDTO;
use App\Domain\User\DTOs\UpdateUserDTO;
use App\Domain\User\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class RefactoredUserController extends Controller
{
    public function __construct(
        private UserService $userService
    ) {}

    /**
     * Get paginated list of users
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = min((int) $request->get('per_page', 15), 100); // Max 100 per page
            $filters = $request->only(['role', 'active', 'search', 'created_from', 'created_to']);

            $users = $this->userService->getPaginatedUsers($perPage, $filters);

            // Transform users to DTOs
            $transformedUsers = $users->getCollection()->map(function ($user) {
                return UserResponseDTO::fromModel($user)->toArray();
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'users' => $transformedUsers,
                    'pagination' => [
                        'current_page' => $users->currentPage(),
                        'last_page' => $users->lastPage(),
                        'per_page' => $users->perPage(),
                        'total' => $users->total(),
                        'from' => $users->firstItem(),
                        'to' => $users->lastItem(),
                    ],
                ],
                'message' => 'Users retrieved successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Get users error', [
                'error' => $e->getMessage(),
                'filters' => $request->only(['role', 'active', 'search']),
                'user_id' => auth()->id(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve users',
                'error' => 'users_retrieval_failed',
            ], 500);
        }
    }

    /**
     * Get a specific user
     */
    public function show(int $id): JsonResponse
    {
        try {
            $user = $this->userService->getUserById($id);

            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                    'error' => 'user_not_found',
                ], 404);
            }

            $userResponse = UserResponseDTO::fromModel($user);

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $userResponse->toArray(),
                ],
                'message' => 'User retrieved successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Get user error', [
                'error' => $e->getMessage(),
                'user_id' => $id,
                'requested_by' => auth()->id(),
                'ip' => request()->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user',
                'error' => 'user_retrieval_failed',
            ], 500);
        }
    }

    /**
     * Create a new user
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $dto = RegisterDTO::fromRequest($request->all());
            $user = $this->userService->createUser($dto);
            $userResponse = UserResponseDTO::fromModel($user);

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $userResponse->toArray(),
                ],
                'message' => 'User created successfully',
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Create user error', [
                'error' => $e->getMessage(),
                'email' => $request->input('email'),
                'created_by' => auth()->id(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create user',
                'error' => 'user_creation_failed',
            ], 500);
        }
    }

    /**
     * Update an existing user
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $dto = UpdateUserDTO::fromRequest($request->all(), $id);
            $user = $this->userService->updateUser($id, $dto);
            $userResponse = UserResponseDTO::fromModel($user);

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $userResponse->toArray(),
                ],
                'message' => 'User updated successfully',
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => 'invalid_argument',
            ], 400);

        } catch (\Exception $e) {
            Log::error('Update user error', [
                'error' => $e->getMessage(),
                'user_id' => $id,
                'updated_by' => auth()->id(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update user',
                'error' => 'user_update_failed',
            ], 500);
        }
    }

    /**
     * Delete a user
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $success = $this->userService->deleteUser($id);

            if (! $success) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                    'error' => 'user_not_found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully',
            ]);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => 'invalid_operation',
            ], 400);

        } catch (\Exception $e) {
            Log::error('Delete user error', [
                'error' => $e->getMessage(),
                'user_id' => $id,
                'deleted_by' => auth()->id(),
                'ip' => request()->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user',
                'error' => 'user_deletion_failed',
            ], 500);
        }
    }

    /**
     * Reset user password
     */
    public function resetPassword(int $id): JsonResponse
    {
        try {
            $temporaryPassword = $this->userService->resetUserPassword($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'temporary_password' => $temporaryPassword,
                ],
                'message' => 'Password reset successfully',
            ]);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => 'user_not_found',
            ], 404);

        } catch (\Exception $e) {
            Log::error('Reset password error', [
                'error' => $e->getMessage(),
                'user_id' => $id,
                'reset_by' => auth()->id(),
                'ip' => request()->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to reset password',
                'error' => 'password_reset_failed',
            ], 500);
        }
    }

    /**
     * Activate/deactivate user
     */
    public function setActive(Request $request, int $id): JsonResponse
    {
        try {
            $active = (bool) $request->input('active', true);
            $success = $this->userService->setUserActive($id, $active);

            if (! $success) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                    'error' => 'user_not_found',
                ], 404);
            }

            $action = $active ? 'activated' : 'deactivated';

            return response()->json([
                'success' => true,
                'message' => "User {$action} successfully",
            ]);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => 'invalid_operation',
            ], 400);

        } catch (\Exception $e) {
            Log::error('Set user active error', [
                'error' => $e->getMessage(),
                'user_id' => $id,
                'active' => $request->input('active'),
                'performed_by' => auth()->id(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to change user status',
                'error' => 'user_status_change_failed',
            ], 500);
        }
    }

    /**
     * Search users
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $query = $request->input('q', '');
            $limit = min((int) $request->input('limit', 10), 50); // Max 50 results

            if (strlen(trim($query)) < 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'Search query must be at least 2 characters',
                    'error' => 'invalid_query',
                ], 400);
            }

            $users = $this->userService->searchUsers($query, $limit);

            // Transform to public data only for search results
            $transformedUsers = $users->map(function ($user) {
                return UserResponseDTO::fromModel($user)->toPublicArray();
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'users' => $transformedUsers,
                    'total' => $users->count(),
                ],
                'message' => 'Search completed successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Search users error', [
                'error' => $e->getMessage(),
                'query' => $request->input('q'),
                'user_id' => auth()->id(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Search failed',
                'error' => 'search_failed',
            ], 500);
        }
    }

    /**
     * Get users by role
     */
    public function getByRole(int $role): JsonResponse
    {
        try {
            $users = $this->userService->getUsersByRole($role);

            $transformedUsers = $users->map(function ($user) {
                return UserResponseDTO::fromModel($user)->toArray();
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'users' => $transformedUsers,
                    'total' => $users->count(),
                ],
                'message' => 'Users retrieved successfully',
            ]);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => 'invalid_role',
            ], 400);

        } catch (\Exception $e) {
            Log::error('Get users by role error', [
                'error' => $e->getMessage(),
                'role' => $role,
                'user_id' => auth()->id(),
                'ip' => request()->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve users',
                'error' => 'users_retrieval_failed',
            ], 500);
        }
    }

    /**
     * Import users from file
     */
    public function import(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:csv,txt|max:2048',
            ]);

            $file = $request->file('file');
            $usersData = $this->parseCSVFile($file);

            if (empty($usersData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No valid user data found in file',
                    'error' => 'empty_file',
                ], 400);
            }

            $results = $this->userService->importUsers($usersData);

            return response()->json([
                'success' => true,
                'data' => $results,
                'message' => 'Import completed',
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Import users error', [
                'error' => $e->getMessage(),
                'imported_by' => auth()->id(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Import failed',
                'error' => 'import_failed',
            ], 500);
        }
    }

    /**
     * Parse CSV file to array
     */
    private function parseCSVFile($file): array
    {
        $usersData = [];
        $handle = fopen($file->getRealPath(), 'r');

        if ($handle !== false) {
            $header = fgetcsv($handle);

            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) >= count($header)) {
                    $usersData[] = array_combine($header, $row);
                }
            }

            fclose($handle);
        }

        return $usersData;
    }
}
