<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class SuperUserSeeder extends Seeder
{
    public function run()
    {
        // Elimina al usuario si ya existe (por email o username)
        DB::table('users')->where('email', 'admin@example.com')->orWhere('username', 'admin')->delete();
    
        // Luego inserta de nuevo
        DB::table('users')->insert([
            'username' => 'admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('Admin123#'),
            'role_id' => 1,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
    
}

