<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\RolesModel;
use Illuminate\Support\Facades\Hash;

class AcudienteSeeder extends Seeder
{
    public function run(): void
    {
        $rol = RolesModel::where('nombre', 'Acudiente')->first();

        if (!$rol) {
            $this->command->error('El rol Acudiente no existe. Ejecuta primero RolesSeeder.');
            return;
        }

        User::updateOrCreate(
            ['email' => 'acudiente@colegio.edu.co'],
            [
                'name' => 'Acudiente Ejemplo',
                'email' => 'acudiente@colegio.edu.co',
                'password' => Hash::make('acudiente123'),
                'roles_id' => $rol->id,
                'email_verified_at' => now(),
            ]
        );
    }
}