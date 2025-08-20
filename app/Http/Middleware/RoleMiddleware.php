<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  string  $roles
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, string $roles)
    {
        // Verificar que el usuario esté autenticado
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        // Obtener los roles permitidos
        $allowedRoles = explode(',', $roles);
        $userRole = (string) $request->user()->role;

        // Verificar si el usuario tiene uno de los roles permitidos
        if (!in_array($userRole, $allowedRoles)) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient permissions. Required roles: ' . $roles . ', your role: ' . $userRole
            ], 403);
        }

        return $next($request);
    }
}