<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Usuario Administrador General
        User::updateOrCreate(
            ['email' => 'admin@ambuu.com'],
            [
                'name' => 'Administrador General',
                'password' => Hash::make('Admin123*'),
                'phone' => '+57 300 000 0000',
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        // Conductor de prueba
        User::updateOrCreate(
            ['email' => 'conductor@ambuu.com'],
            [
                'name' => 'Carlos Conductor',
                'password' => Hash::make('Driver123*'),
                'phone' => '+57 300 111 2222',
                'role' => 'driver',
                'is_active' => true,
            ]
        );
    }
}
