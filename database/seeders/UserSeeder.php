<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SuperUserSeeder extends Seeder
{
    public function run(): void
    {
        // Evitar duplicados
        if (!User::where('email', 'admin@example.com')->exists()) {
            User::create([
                'name' => 'admin',
                'email' => 'admin@example.com',
                'password' => Hash::make('Admin123'),
                'role_id' => 1, // asegúrate de que esta columna exista y tenga una FK válida si aplica
            ]);
        }
    }
}
