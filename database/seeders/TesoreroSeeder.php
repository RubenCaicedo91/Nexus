<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\RolesModel;
use Illuminate\Support\Facades\Hash;

class TesoreroSeeder extends Seeder
{
    public function run(): void
    {
        $rol = RolesModel::where('nombre', 'tesorero')->first();

        if (!$rol) {
            $this->command->error('El rol Tesorero no existe. Ejecuta primero RolesSeeder.');
            return;
        }

        User::updateOrCreate(
            ['email' => 'tesorero@colegio.edu.co'],
            [
                'name' => 'Tesorero del Colegio',
                'email' => 'tesorero@colegio.edu.co',
                'password' => Hash::make('tesorero123'),
                'roles_id' => $rol->id,
                'email_verified_at' => now(),
            ]
        );
    }
}
