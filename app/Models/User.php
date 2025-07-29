<?php
// app/Models/User.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'email',
        'password',
        'role_id',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'email_verified_at' => 'datetime',
        'password' => 'hashed', // Esto está bien, Laravel lo maneja automáticamente
    ];

    // ELIMINAR ESTOS MÉTODOS - CAUSAN PROBLEMAS:
    // - getAuthPassword()
    // - getPasswordAttribute() 
    // - setPasswordAttribute()
    
    // Laravel ya maneja automáticamente el campo password cuando usas:
    // protected $casts = ['password' => 'hashed'];

    /**
     * Relación con rol
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Relación con permisos
     */
    public function permissions()
    {
        return $this->hasOne(UserPermission::class);
    }

    /**
     * Obtener permisos del usuario
     */
    public function getUserPermissions()
    {
        $permissions = $this->permissions;
        
        if ($permissions) {
            // Si el usuario tiene permisos personalizados, usarlos
            // Pero asegurarse que solo los administradores (role_id 1) puedan ver ventas
            return [
                'facturar' => $permissions->facturar,
                'verVentas' => $permissions->verVentas && $this->role_id == 1
            ];
        } else {
            // Si no tiene permisos personalizados, asignar permisos según el rol
            if ($this->role_id == 1) { // Superuser - tiene todos los permisos
                $defaultPermissions = [
                    'facturar' => true,
                    'verVentas' => true
                ];
            } else {
                $defaultPermissions = [
                    'facturar' => in_array($this->role_id, [1, 2]), // Superuser y staff pueden facturar
                    'verVentas' => false // Solo superuser puede ver ventas
                ];
            }
            
            // Crear un registro de permisos para este usuario
            $this->permissions()->create($defaultPermissions);
            
            return $defaultPermissions;
        }
    }

    /**
     * Verificar si el usuario puede facturar
     */
    public function canFacturar()
    {
        $permissions = $this->getUserPermissions();
        return $permissions['facturar'];
    }

    /**
     * Verificar si el usuario puede ver ventas
     */
    public function canVerVentas()
    {
        $permissions = $this->getUserPermissions();
        return $permissions['verVentas'];
    }
}