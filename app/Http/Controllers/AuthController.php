<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Requests\UserRegisterRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(UserRegisterRequest $request)
    {
        $validatedData = $request->validated();
        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => bcrypt($validatedData['password']),
            'ci' => $validatedData['ci'],
            'phone' => $validatedData['phone'] ?? null,
            'bio' => $validatedData['bio'] ?? null,
        ]);
        $token = auth('api')->login($user);
        return $this->respondWithToken($token);
    }
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!$token = auth('api')->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = auth('api')->user(); // Asegúrate de obtener el usuario autenticado

        // Verificar si el usuario necesita cambiar su contraseña
        // password_changed = 0 significa que NO ha cambiado, = 1 significa que SÍ cambió
        if ($user->password_changed == 0) {
            return response()->json([
                'message' => 'Password change required',
                'password_change_required' => true,
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60,
                'user' => $user,
            ]);
        }

        // Usuario YA cambió contraseña, login normal
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'user' => $user,
            'password_change_required' => false, // Explícitamente FALSE
        ]);
    }
    public function me()
    {
        return response()->json(auth()->user());
    }

    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'user' => auth('api')->user(),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'bio' => 'nullable|string|max:1000',
            'phone' => 'nullable|string|max:20',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6',
            'avatar' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->has('photo')) {
            $user->avatar = $request->photo;
        }

        $user->name = $request->input('name');
        $user->bio = $request->input('bio');
        $user->phone = $request->input('phone');
        $user->email = $request->input('email');

        if ($request->input('password')) {
            $user->password = bcrypt($request->input('password'));
        }

        $user->save();

        return response()->json(['message' => 'Profile updated successfully', 'user' => $user], 200);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'password' => 'required|string|min:8',
        ]);

        $user = Auth::user();
        $user->password = bcrypt($request->password);
        $user->password_changed = true;
        $user->save();

        return response()->json(['message' => 'Password changed successfully']);
    }
}
