<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\RolesModel;
use Illuminate\Support\Facades\Hash;

class DocenteSeeder extends Seeder
{
    public function run(): void
    {
        $rol = RolesModel::where('nombre', 'Docente')->first();

        if (!$rol) {
            $this->command->error('El rol Docente no existe. Ejecuta primero RolesSeeder.');
            return;
        }

        User::updateOrCreate(
            ['email' => 'docente@colegio.edu.co'],
            [
                'name' => 'Docente',
                'email' => 'docente@colegio.edu.co',
                'password' => Hash::make('docente123'),
                'roles_id' => $rol->id,
                'email_verified_at' => now(),
            ]
        );
    }
}
