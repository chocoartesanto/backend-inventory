<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SuperuserMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Verificar que el usuario esté autenticado
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado'
            ], 401);
        }

        // Verificar que el usuario tenga rol de superusuario (role_id = 1)
        if ($request->user()->role_id !== 1) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para realizar esta acción. Se requiere rol de superusuario.'
            ], 403);
        }

        return $next($request);
    }
}