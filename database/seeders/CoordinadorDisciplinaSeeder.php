<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\RolesModel;
use Illuminate\Support\Facades\Hash;

class CoordinadorDisciplinaSeeder extends Seeder
{
    public function run(): void
    {
        $rol = RolesModel::where('nombre', 'Coordinador de Disciplina')->first();

        if (!$rol) {
            $this->command->error('El rol de Coordinador de Disciplina no existe. Ejecuta primero RolesSeeder.');
            return;
        }

        User::updateOrCreate(
            ['email' => 'coordinador.disciplina@colegio.edu.co'],
            [
                'name' => 'Coordinador de Disciplina',
                'email' => 'coordinador.disciplina@colegio.edu.co',
                'password' => Hash::make('disciplina123'),
                'roles_id' => $rol->id,
                'email_verified_at' => now(),
            ]
        );
    }
}
