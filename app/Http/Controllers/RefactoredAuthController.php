<?php

namespace App\Http\Controllers;

use App\Domain\Auth\DTOs\ChangePasswordDTO;
use App\Domain\Auth\DTOs\LoginDTO;
use App\Domain\Auth\DTOs\RegisterDTO;
use App\Domain\Auth\DTOs\UserResponseDTO;
use App\Domain\Auth\Services\AuthService;
use App\Domain\User\DTOs\UpdateUserDTO;
use App\Domain\User\Services\UserService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class RefactoredAuthController extends Controller
{
    public function __construct(
        private AuthService $authService,
        private UserService $userService
    ) {}

    /**
     * User login
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $dto = LoginDTO::fromRequest($request->all());
            $authResponse = $this->authService->login($dto);

            return response()->json([
                'success' => true,
                'data' => $authResponse->toArray(),
                'message' => 'Login successful',
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (AuthenticationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => 'authentication_failed',
            ], 401);

        } catch (\Exception $e) {
            Log::error('Login controller error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred',
                'error' => 'internal_server_error',
            ], 500);
        }
    }

    /**
     * User registration
     */
    public function register(Request $request): JsonResponse
    {
        try {
            $dto = RegisterDTO::fromRequest($request->all());
            $authResponse = $this->authService->register($dto);

            return response()->json([
                'success' => true,
                'data' => $authResponse->toArray(),
                'message' => 'Registration successful',
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Registration controller error', [
                'error' => $e->getMessage(),
                'email' => $request->input('email'),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
                'error' => 'registration_failed',
            ], 500);
        }
    }

    /**
     * Get current authenticated user
     */
    public function me(): JsonResponse
    {
        try {
            $user = $this->authService->getCurrentUser();

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
                'data' => $userResponse->toArray(),
                'message' => 'User data retrieved successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Get current user error', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'ip' => request()->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user data',
                'error' => 'user_retrieval_failed',
            ], 500);
        }
    }

    /**
     * User logout
     */
    public function logout(): JsonResponse
    {
        try {
            $this->authService->logout();

            return response()->json([
                'success' => true,
                'message' => 'Successfully logged out',
            ]);

        } catch (\Exception $e) {
            Log::error('Logout controller error', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'ip' => request()->ip(),
            ]);

            // Even if there's an error, we should return success
            // as the frontend should clear the token anyway
            return response()->json([
                'success' => true,
                'message' => 'Logged out',
            ]);
        }
    }

    /**
     * Refresh JWT token
     */
    public function refresh(): JsonResponse
    {
        try {
            $newToken = $this->authService->refreshToken();

            return response()->json([
                'success' => true,
                'data' => [
                    'access_token' => $newToken,
                    'token_type' => 'bearer',
                    'expires_in' => auth('api')->factory()->getTTL() * 60,
                ],
                'message' => 'Token refreshed successfully',
            ]);

        } catch (AuthenticationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => 'token_refresh_failed',
            ], 401);

        } catch (\Exception $e) {
            Log::error('Token refresh error', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'ip' => request()->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to refresh token',
                'error' => 'token_refresh_failed',
            ], 500);
        }
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        try {
            $userId = auth()->id();
            $dto = UpdateUserDTO::fromRequest($request->all(), $userId);

            $updatedUser = $this->userService->updateUser($userId, $dto);
            $userResponse = UserResponseDTO::fromModel($updatedUser);

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $userResponse->toArray(),
                ],
                'message' => 'Profile updated successfully',
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Profile update error', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'ip' => request()->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile',
                'error' => 'profile_update_failed',
            ], 500);
        }
    }

    /**
     * Change user password
     */
    public function changePassword(Request $request): JsonResponse
    {
        try {
            $userId = auth()->id();
            $dto = ChangePasswordDTO::fromRequest($request->all(), $userId);

            $success = $this->authService->changePassword($dto);

            if (! $success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to change password',
                    'error' => 'password_change_failed',
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Password changed successfully',
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (AuthenticationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => 'authentication_failed',
            ], 401);

        } catch (\Exception $e) {
            Log::error('Password change error', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'ip' => request()->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to change password',
                'error' => 'password_change_failed',
            ], 500);
        }
    }

    /**
     * Get user statistics (admin only)
     */
    public function getUserStatistics(): JsonResponse
    {
        try {
            $statistics = $this->userService->getUserStatistics();

            return response()->json([
                'success' => true,
                'data' => $statistics,
                'message' => 'Statistics retrieved successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Get user statistics error', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'ip' => request()->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve statistics',
                'error' => 'statistics_retrieval_failed',
            ], 500);
        }
    }
}
