<?php
// app/Http/Controllers/Api/AuthController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login de usuario
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de entrada inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        // Buscar usuario por username
        $user = User::with('role')->where('username', $request->username)->first();

        // CORREGIDO: Usar 'password' en lugar de 'hashed_password'
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales incorrectas'
            ], 401);
        }

        // Verificar si el usuario está activo
        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Cuenta de usuario inactiva'
            ], 403);
        }

        // Crear token
        $tokenExpiry = (int) env('ACCESS_TOKEN_EXPIRE_MINUTES', 1440);
        $token = $user->createToken('auth-token', ['*'], now()->addMinutes($tokenExpiry));

        // Obtener permisos del usuario
        $permissions = $user->getUserPermissions();

        return response()->json([
            'success' => true,
            'message' => 'Login exitoso',
            'data' => [
                'access_token' => $token->plainTextToken,
                'token_type' => 'bearer',
                'expires_in' => $tokenExpiry * 60, // en segundos
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'role_id' => $user->role_id,
                    'role_name' => $user->role->name ?? null,
                    'is_active' => $user->is_active,
                    'permissions' => $permissions,
                    'created_at' => $user->created_at,
                ]
            ]
        ]);
    }

    /**
     * Logout de usuario
     */
    public function logout(Request $request)
    {
        // Revocar el token actual
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout exitoso'
        ]);
    }

    /**
     * Obtener información del usuario actual
     */
    public function me(Request $request)
    {
        $user = $request->user()->load('role');
        $permissions = $user->getUserPermissions();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'role_id' => $user->role_id,
                'role_name' => $user->role->name ?? null,
                'is_active' => $user->is_active,
                'permissions' => $permissions,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ]
        ]);
    }

    /**
     * Refresh token
     */
    public function refresh(Request $request)
    {
        $user = $request->user();
        
        // Revocar el token actual
        $request->user()->currentAccessToken()->delete();
        
        // Crear nuevo token
        $tokenExpiry = (int) env('ACCESS_TOKEN_EXPIRE_MINUTES', 1440);
        $token = $user->createToken('auth-token', ['*'], now()->addMinutes($tokenExpiry));

        return response()->json([
            'success' => true,
            'message' => 'Token renovado exitosamente',
            'data' => [
                'access_token' => $token->plainTextToken,
                'token_type' => 'bearer',
                'expires_in' => $tokenExpiry * 60, // en segundos
            ]
        ]);
    }
}