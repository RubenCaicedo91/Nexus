<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\RolesModel;
use Illuminate\Support\Facades\Hash;

class EstudianteSeeder extends Seeder
{
    public function run(): void
    {
        $rol = RolesModel::where('nombre', 'Estudiante')->first();

        if (!$rol) {
            $this->command->error('El rol Estudiante no existe. Ejecuta primero RolesSeeder.');
            return;
        }

        User::updateOrCreate(
            ['email' => 'estudiante@colegio.edu.co'],
            [
                'name' => 'Estudiante Ejemplo',
                'email' => 'estudiante@colegio.edu.co',
                'password' => Hash::make('estudiante123'),
                'roles_id' => $rol->id,
                'email_verified_at' => now(),
            ]
        );
    }
}
