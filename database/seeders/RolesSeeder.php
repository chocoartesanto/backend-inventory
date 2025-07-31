<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Verificar si ya existen roles para evitar duplicados
        if (Role::count() == 0) {
            $roles = [
                [
                    'id' => 1,
                    'name' => 'Superusuario',
                    'description' => 'Administrador con acceso completo al sistema'
                ],
                [
                    'id' => 2, 
                    'name' => 'Staff',
                    'description' => 'Usuario con permisos limitados'
                ],
                [
                    'id' => 3,
                    'name' => 'Usuario',
                    'description' => 'Usuario básico con permisos mínimos'
                ]
            ];

            foreach ($roles as $role) {
                Role::create($role);
            }

            $this->command->info('Roles creados exitosamente');
        } else {
            $this->command->info('Los roles ya existen en la base de datos');
        }
    }
}