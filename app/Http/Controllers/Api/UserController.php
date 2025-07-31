<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\UserPermission;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    // Eliminamos el constructor completamente y manejaremos la autenticación en las rutas

    /**
     * Verificar si el usuario actual es superusuario
     */
    private function checkSuperuser()
    {
        $user = auth()->user();
        
        if (!$user || $user->role_id !== 1) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para realizar esta acción. Solo los superusuarios pueden gestionar usuarios.'
            ], 403);
        }
        
        return null; // null significa que está autorizado
    }

    /**
     * Obtener todos los usuarios (solo superusuarios)
     */
    public function index(Request $request)
    {
        // Verificar permisos de superusuario
        $authCheck = $this->checkSuperuser();
        if ($authCheck) return $authCheck;

        $skip = $request->get('skip', 0);
        $limit = $request->get('limit', 100);
        $search = $request->get('search');

        $query = User::with(['role', 'permissions'])
            ->select('users.*', 'roles.name as role_name')
            ->leftJoin('roles', 'users.role_id', '=', 'roles.id');

        // Aplicar filtro de búsqueda si existe
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('users.username', 'LIKE', "%{$search}%")
                  ->orWhere('users.email', 'LIKE', "%{$search}%");
            });
        }

        $users = $query->orderBy('users.id')
            ->offset($skip)
            ->limit($limit)
            ->get();

        // Contar el total de usuarios (sin paginación)
        $totalQuery = User::query();
        if ($search) {
            $totalQuery->where(function ($q) use ($search) {
                $q->where('username', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }
        $total = $totalQuery->count();

        // Formatear la respuesta para incluir permisos
        $formattedUsers = $users->map(function ($user) {
            return [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'role_id' => $user->role_id,
             'role_name' => $user->role_name,
                'is_active' => $user->is_active,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
                'permissions' => [
                    'facturar' => $user->permissions ? $user->permissions->facturar : true,
                    'verVentas' => $user->permissions ? $user->permissions->verVentas : false,
                ]
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formattedUsers,
            'meta' => [
                'total' => $total,
                'skip' => $skip,
                'limit' => $limit
            ]
        ]);
    }

    /**
     * Obtener un usuario por su ID (solo superusuarios)
     */
    public function show($id)
    {
        // Verificar permisos de superusuario
        $authCheck = $this->checkSuperuser();
        if ($authCheck) return $authCheck;

        $user = User::with(['role', 'permissions'])
            ->select('users.*', 'roles.name as role_name')
            ->leftJoin('roles', 'users.role_id', '=', 'roles.id')
            ->where('users.id', $id)
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => "Usuario con ID {$id} no encontrado"
            ], 404);
        }

        $formattedUser = [
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'role_id' => $user->role_id,
            'role_name' => $user->role_name,
            'is_active' => $user->is_active,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
            'permissions' => [
                'facturar' => $user->permissions ? $user->permissions->facturar : true,
                'verVentas' => $user->permissions ? $user->permissions->verVentas : false,
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $formattedUser
        ]);
    }

    /**
     * Crear un nuevo usuario (solo superusuarios)
     */
    public function store(Request $request)
    {
        // Verificar permisos de superusuario
        $authCheck = $this->checkSuperuser();
        if ($authCheck) return $authCheck;

        // Validación
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:50|unique:users,username',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
            'role_id' => 'required|exists:roles,id',
            'permissions' => 'sometimes|array',
            'permissions.facturar' => 'sometimes|boolean',
            'permissions.verVentas' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], 400);
        }

        DB::beginTransaction();
        
        try {
            // Crear el usuario
            $user = User::create([
                'username' => $request->username,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role_id' => $request->role_id,
                'is_active' => true,
            ]);

            // Establecer permisos por defecto
            $permissions = [
                'facturar' => true,  // Por defecto todos pueden facturar
                'verVentas' => false // Por defecto nadie puede ver ventas
            ];

            // Si se proporcionan permisos específicos, usarlos con restricciones
            if ($request->has('permissions')) {
                $requestPermissions = $request->permissions;
                
                if (isset($requestPermissions['facturar'])) {
                    $permissions['facturar'] = $requestPermissions['facturar'];
                }
                
                // Solo permitir verVentas=true si el rol es 1 (superusuario)
                if (isset($requestPermissions['verVentas']) && $request->role_id == 1) {
                    $permissions['verVentas'] = $requestPermissions['verVentas'];
                }
            }

            // Crear los permisos del usuario
            UserPermission::create([
                'user_id' => $user->id,
                'facturar' => $permissions['facturar'],
                'verVentas' => $permissions['verVentas'],
            ]);

            DB::commit();

            // Obtener el usuario recién creado con sus relaciones
            $newUser = User::with(['role', 'permissions'])
                ->select('users.*', 'roles.name as role_name')
                ->leftJoin('roles', 'users.role_id', '=', 'roles.id')
                ->where('users.id', $user->id)
                ->first();

            $formattedUser = [
                'id' => $newUser->id,
                'username' => $newUser->username,
                'email' => $newUser->email,
                'role_id' => $newUser->role_id,
                 'role_name' => $newUser->role_name,
                'is_active' => $newUser->is_active,
                'created_at' => $newUser->created_at,
                'updated_at' => $newUser->updated_at,
                'permissions' => [
                    'facturar' => $newUser->permissions->facturar,
                    'verVentas' => $newUser->permissions->verVentas,
                ]
            ];

            return response()->json([
                'success' => true,
                'message' => 'Usuario creado exitosamente',
                'data' => $formattedUser
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el usuario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar un usuario (solo superusuarios)
     */
    public function update(Request $request, $id)
    {
        // Verificar permisos de superusuario
        $authCheck = $this->checkSuperuser();
        if ($authCheck) return $authCheck;

        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => "Usuario con ID {$id} no encontrado"
            ], 404);
        }

        // Validación
        $validator = Validator::make($request->all(), [
            'username' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('users', 'username')->ignore($id)
            ],
            'email' => [
                'sometimes',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($id)
            ],
            'password' => 'sometimes|string|min:6',
            'role_id' => 'sometimes|exists:roles,id',
            'is_active' => 'sometimes|boolean',
            'permissions' => 'sometimes|array',
            'permissions.facturar' => 'sometimes|boolean',
            'permissions.verVentas' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], 400);
        }

        DB::beginTransaction();

        try {
            // Actualizar campos del usuario
            $updateData = [];
            
            if ($request->has('username')) {
                $updateData['username'] = $request->username;
            }
            
            if ($request->has('email')) {
                $updateData['email'] = $request->email;
            }
            
            if ($request->has('password')) {
                $updateData['password'] = Hash::make($request->password);
            }
            
            if ($request->has('role_id')) {
                $updateData['role_id'] = $request->role_id;
            }
            
            if ($request->has('is_active')) {
                $updateData['is_active'] = $request->is_active;
            }

            if (!empty($updateData)) {
                $user->update($updateData);
            }

            // Actualizar permisos si se proporcionaron
            if ($request->has('permissions')) {
                $permissions = $request->permissions;
                
                // Obtener el rol actual del usuario
                $currentRoleId = $request->has('role_id') ? $request->role_id : $user->role_id;
                
                // Asegurarse que solo usuarios con role_id 1 (admin) pueden tener verVentas=True
                $canSeeSales = isset($permissions['verVentas']) && $permissions['verVentas'] && $currentRoleId == 1;
                
                $permissionData = [
                    'facturar' => isset($permissions['facturar']) ? $permissions['facturar'] : false,
                    'verVentas' => $canSeeSales,
                ];

                // Actualizar o crear permisos
                UserPermission::updateOrCreate(
                    ['user_id' => $user->id],
                    $permissionData
                );
            }

            DB::commit();

            // Obtener el usuario actualizado con sus relaciones
            $updatedUser = User::with(['role', 'permissions'])
                ->select('users.*', 'roles.name as role_name')
                ->leftJoin('roles', 'users.role_id', '=', 'roles.id')
                ->where('users.id', $user->id)
                ->first();

            $formattedUser = [
                'id' => $updatedUser->id,
                'username' => $updatedUser->username,
                'email' => $updatedUser->email,
                'role_id' => $updatedUser->role_id,
                 'role_name' => $updatedUser->role_name,
                'is_active' => $updatedUser->is_active,
                'created_at' => $updatedUser->created_at,
                'updated_at' => $updatedUser->updated_at,
                'permissions' => [
                    'facturar' => $updatedUser->permissions ? $updatedUser->permissions->facturar : false,
                    'verVentas' => $updatedUser->permissions ? $updatedUser->permissions->verVentas : false,
                ]
            ];

            return response()->json([
                'success' => true,
                'message' => 'Usuario actualizado exitosamente',
                'data' => $formattedUser
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el usuario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Desactivar un usuario (soft delete)
     */
    public function destroy($id)
    {
        // Verificar permisos de superusuario
        $authCheck = $this->checkSuperuser();
        if ($authCheck) return $authCheck;

        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => "Usuario con ID {$id} no encontrado"
            ], 404);
        }

        // No permitir eliminar al propio superusuario
        if ($id == auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes desactivar tu propia cuenta'
            ], 400);
        }

        // Desactivar el usuario (soft delete)
        $user->update(['is_active' => false]);

        // Obtener el usuario actualizado
        $updatedUser = User::with(['role'])
            ->select('users.*', 'roles.name as role_name')
            ->leftJoin('roles', 'users.role_id', '=', 'roles.id')
            ->where('users.id', $id)
            ->first();

        $formattedUser = [
            'id' => $updatedUser->id,
            'username' => $updatedUser->username,
            'email' => $updatedUser->email,
            'role_id' => $updatedUser->role_id,
            'role_name' => $updatedUser->role_name,
            'is_active' => $updatedUser->is_active,
            'created_at' => $updatedUser->created_at,
            'updated_at' => $updatedUser->updated_at,
        ];

        return response()->json([
            'success' => true,
            'message' => 'Usuario desactivado exitosamente',
            'data' => $formattedUser
        ]);
    }

    /**
     * Activar un usuario que ha sido desactivado
     */
    public function activate($id)
    {
        // Verificar permisos de superusuario
        $authCheck = $this->checkSuperuser();
        if ($authCheck) return $authCheck;

        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => "Usuario con ID {$id} no encontrado"
            ], 404);
        }

        // Verificar si el usuario ya está activo
        if ($user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'El usuario ya está activo'
            ], 400);
        }

        // Activar el usuario
        $user->update(['is_active' => true]);

        // Obtener el usuario actualizado
        $updatedUser = User::with(['role'])
            ->select('users.*', 'roles.name as role_name')
            ->leftJoin('roles', 'users.role_id', '=', 'roles.id')
            ->where('users.id', $id)
            ->first();

        $formattedUser = [
            'id' => $updatedUser->id,
            'username' => $updatedUser->username,
            'email' => $updatedUser->email,
            'role_id' => $updatedUser->role_id,
           'role_name' => $updatedUser->role_name,
            'is_active' => $updatedUser->is_active,
            'created_at' => $updatedUser->created_at,
            'updated_at' => $updatedUser->updated_at,
        ];

        return response()->json([
            'success' => true,
            'message' => 'Usuario activado exitosamente',
            'data' => $formattedUser
        ]);
    }

    /**
     * Eliminar un usuario permanentemente de la base de datos
     */
/**
 * Eliminar un usuario permanentemente de la base de datos
 */
public function permanentDelete($id)
{
    // Verificar permisos de superusuario
    $authCheck = $this->checkSuperuser();
    if ($authCheck) return $authCheck;

    $user = User::find($id);

    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => "Usuario con ID {$id} no encontrado"
        ], 404);
    }

    // No permitir eliminar al propio superusuario
    if ($id == auth()->id()) {
        return response()->json([
            'success' => false,
            'message' => 'No puedes eliminar tu propia cuenta permanentemente'
        ], 400);
    }

    DB::beginTransaction();

    try {
        $username = $user->username;

        // 1. Verificar si la tabla sales existe y tiene la columna user_id
        if (DB::getSchemaBuilder()->hasTable('sales')) {
            $salesColumns = DB::getSchemaBuilder()->getColumnListing('sales');
            if (in_array('user_id', $salesColumns)) {
                DB::table('sales')->where('user_id', $id)->update(['user_id' => null]);
            }
        }

        // 2. Verificar si la tabla purchases existe y tiene la columna seller_username
        if (DB::getSchemaBuilder()->hasTable('purchases')) {
            $purchasesColumns = DB::getSchemaBuilder()->getColumnListing('purchases');
            if (in_array('seller_username', $purchasesColumns)) {
                DB::table('purchases')->where('seller_username', $username)->update(['seller_username' => null]);
            }
        }

        // 3. Eliminar permisos del usuario (respaldo por si ON DELETE CASCADE no funciona)
        UserPermission::where('user_id', $id)->delete();

        // 4. Eliminar el usuario
        $user->delete();

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Usuario eliminado permanentemente'
        ], 204);

    } catch (\Exception $e) {
        DB::rollBack();
        
        return response()->json([
            'success' => false,
            'message' => 'Error al eliminar permanentemente al usuario: ' . $e->getMessage()
        ], 500);
    }
}
}