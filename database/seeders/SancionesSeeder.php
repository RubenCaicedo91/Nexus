<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Sancion;
use App\Models\User;
use Carbon\Carbon;

class SancionesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear algunas sanciones de ejemplo asociadas al estudiante seeded
        $student = User::where('email', 'estudiante@colegio.edu.co')->first();
        if (! $student) {
            $this->command->info('No se encontró el usuario estudiante@colegio.edu.co; omitiendo creación de sanciones de ejemplo.');
            return;
        }

        $now = Carbon::now()->toDateString();

        $sanciones = [
            ['usuario_id' => $student->id, 'descripcion' => 'Amonestación verbal por comportamiento en clase', 'tipo' => 'Amonestación verbal', 'fecha' => $now],
            ['usuario_id' => $student->id, 'descripcion' => 'Suspensión 1 día por incumplimiento', 'tipo' => 'Suspensión temporal', 'fecha' => $now],
        ];

        foreach ($sanciones as $s) {
            Sancion::updateOrCreate([
                'usuario_id' => $s['usuario_id'],
                'descripcion' => $s['descripcion']
            ], $s);
        }
    }
}
