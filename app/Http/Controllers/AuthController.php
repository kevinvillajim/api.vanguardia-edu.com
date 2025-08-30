<?php

namespace App\Http\Controllers;

use App\Domain\Auth\DTOs\ChangePasswordDTO;
use App\Domain\Auth\DTOs\LoginDTO;
use App\Domain\Auth\DTOs\RegisterDTO;
use App\Domain\Auth\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}

    /**
     * User login
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            $dto = new LoginDTO(
                email: $request->email,
                password: $request->password,
                ip: $request->ip(),
                userAgent: $request->userAgent()
            );

            $response = $this->authService->login($dto);

            return response()->json([
                'success' => true,
                'user' => $response->user,
                'token' => $response->token,
                'access_token' => $response->token,
                'token_type' => 'Bearer',
                'expires_in' => config('jwt.ttl') * 60,
                'passwordChangeRequired' => $response->passwordChangeRequired ?? false,
                'message' => 'Login successful'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 401);
        }
    }

    /**
     * User registration
     */
    public function register(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:8|confirmed',
            ]);

            $dto = new RegisterDTO(
                name: $request->name,
                email: $request->email,
                password: $request->password,
                ip: $request->ip(),
                userAgent: $request->userAgent()
            );

            $response = $this->authService->register($dto);

            return response()->json([
                'success' => true,
                'data' => [
                    'access_token' => $response->token,
                    'token_type' => 'Bearer',
                    'expires_in' => config('jwt.ttl') * 60,
                    'user' => $response->user,
                ],
                'message' => 'Registration successful'
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get authenticated user
     */
    public function me(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'User not authenticated'
                ], 401);
            }

            return response()->json([
                'success' => true,
                'data' => $user,
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'role' => $user->role,
                'active' => $user->active
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * User logout
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $this->authService->logout();
            
            return response()->json([
                'success' => true,
                'message' => 'Successfully logged out'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Refresh JWT token
     */
    public function refresh(Request $request): JsonResponse
    {
        try {
            $newToken = JWTAuth::refresh();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'access_token' => $newToken,
                    'token_type' => 'Bearer',
                    'expires_in' => config('jwt.ttl') * 60,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Token refresh failed'
            ], 401);
        }
    }

    /**
     * Change password
     */
    public function changePassword(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'password' => 'required|string|min:8|confirmed',
            ]);

            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'User not authenticated'
                ], 401);
            }

            $dto = new ChangePasswordDTO(
                userId: $user->id,
                newPassword: $request->password,
                ip: $request->ip(),
                userAgent: $request->userAgent()
            );

            $this->authService->changePassword($dto);

            return response()->json([
                'success' => true,
                'message' => 'Password changed successfully'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }
}