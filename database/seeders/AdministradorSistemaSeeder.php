<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\RolesModel;
use Illuminate\Support\Facades\Hash;

class AdministradorSistemaSeeder extends Seeder
{
    public function run(): void
    {
        $rol = RolesModel::where('nombre', 'Administrador_sistema')->first();

        if (!$rol) {
            $this->command->error('El rol Administrador_sistema no existe. Ejecuta primero RolesSeeder.');
            return;
        }

        User::updateOrCreate(
            ['email' => 'admin@colegio.edu.co'],
            [
                'name' => 'Administrador del Sistema',
                'email' => 'admin@colegio.edu.co',
                'password' => Hash::make('admin123'),
                'roles_id' => $rol->id,
                'email_verified_at' => now(),
            ]
        );
    }
}
