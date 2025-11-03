<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Matricula;
use App\Models\User;
use Carbon\Carbon;

class MatriculasSeeder extends Seeder
{
    public function run()
    {
        $user = User::where('email', 'estudiante@colegio.edu.co')->first();
        if (! $user) {
            $this->command->info('No existe el usuario estudiante@colegio.edu.co');
            return;
        }

        $exists = Matricula::where('user_id', $user->id)->exists();
        if ($exists) {
            $this->command->info('La matrícula del estudiante ya existe.');
            return;
        }

        Matricula::create([
            'user_id' => $user->id,
            'curso_id' => 1,
            'fecha_matricula' => Carbon::now()->format('Y-m-d'),
            'estado' => 'activa',
        ]);

        $this->command->info('Matrícula creada para ' . $user->email);
    }
}
