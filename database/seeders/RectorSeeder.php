<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\RolesModel;
use Illuminate\Support\Facades\Hash;

class RectorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buscar el rol de Rector
        $rolRector = RolesModel::where('nombre', 'Rector')->first();
        
        if (!$rolRector) {
            $this->command->error('El rol de Rector no existe. Ejecuta primero RolesSeeder.');
            return;
        }

        // Crear usuario rector
        User::updateOrCreate(
            ['email' => 'rector@colegio.edu.co'],
            [
                'name' => 'Rector del Colegio',
                'email' => 'rector@colegio.edu.co',
                'password' => Hash::make('rector123'), // Cambiar por una contraseña segura
                'roles_id' => $rolRector->id,
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('Usuario Rector creado exitosamente.');
        $this->command->info('Email: rector@colegio.edu.co');
        $this->command->info('Contraseña: rector123');
        $this->command->warn('¡IMPORTANTE! Cambia la contraseña después del primer login.');
    }
}