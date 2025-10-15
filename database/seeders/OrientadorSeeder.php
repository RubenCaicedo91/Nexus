<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\RolesModel;
use Illuminate\Support\Facades\Hash;

class OrientadorSeeder extends Seeder
{
    public function run(): void
    {
        $rol = RolesModel::where('nombre', 'orientador')->first();

        if (!$rol) {
            $this->command->error('El rol Orientador no existe. Ejecuta primero RolesSeeder.');
            return;
        }

        User::updateOrCreate(
            ['email' => 'orientador@colegio.edu.co'],
            [
                'name' => 'Orientador Ejemplo',
                'email' => 'orientador@colegio.edu.co',
                'password' => Hash::make('orientador123'),
                'roles_id' => $rol->id,
                'email_verified_at' => now(),
            ]
        );
    }
}
