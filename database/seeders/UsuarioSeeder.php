<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsuarioSeeder extends Seeder
{
    public function run(): void
    {
        // [SEGURIDAD] Usuario exclusivo de desarrollo.
        User::firstOrCreate(
            [
                'email' => 'admin@biela.test',
            ],
            [
                'name' => 'Administrador',
                'password' => Hash::make('Biela2026'),
            ]
        );
    }
}
