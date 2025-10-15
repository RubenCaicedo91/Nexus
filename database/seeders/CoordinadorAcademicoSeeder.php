<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\RolesModel;
use Illuminate\Support\Facades\Hash;

class CoordinadorAcademicoSeeder extends Seeder
{
    public function run(): void
    {
        $rol = RolesModel::where('nombre', 'Coordinador Académico')->first();

        if (!$rol) {
            $this->command->error('El rol de Coordinador Académico no existe. Ejecuta primero RolesSeeder.');
            return;
        }

        User::updateOrCreate(
            ['email' => 'coordinador.academico@colegio.edu.co'],
            [
                'name' => 'Coordinador Académico',
                'email' => 'coordinador.academico@colegio.edu.co',
                'password' => Hash::make('coord123'),
                'roles_id' => $rol->id,
                'email_verified_at' => now(),
            ]
        );
    }
}
