<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SancionTiposSeeder extends Seeder
{
    public function run()
    {
        $now = Carbon::now();
        $tipos = [
            ['nombre' => 'Amonestación verbal', 'descripcion' => 'Advertencia verbal registrada en sistema', 'categoria' => 'normal', 'severidad' => 'baja', 'duracion_defecto_dias' => null, 'activo' => true],
            ['nombre' => 'Amonestación escrita', 'descripcion' => 'Advertencia escrita que queda en expediente', 'categoria' => 'normal', 'severidad' => 'baja', 'duracion_defecto_dias' => null, 'activo' => true],
            ['nombre' => 'Llamado de atención', 'descripcion' => 'Reprimenda formal en clase', 'categoria' => 'normal', 'severidad' => 'media', 'duracion_defecto_dias' => null, 'activo' => true],
            ['nombre' => 'Salida del aula', 'descripcion' => 'Expulsión temporal del salón', 'categoria' => 'normal', 'severidad' => 'media', 'duracion_defecto_dias' => 0, 'activo' => true],
            ['nombre' => 'Detención / Hora de recuperación', 'descripcion' => 'Tiempo extra de supervisión o tarea', 'categoria' => 'normal', 'severidad' => 'media', 'duracion_defecto_dias' => 0, 'activo' => true],
            ['nombre' => 'Pérdida de privilegios', 'descripcion' => 'Restricción de participaciones o actividades', 'categoria' => 'privileges', 'severidad' => 'media', 'duracion_defecto_dias' => null, 'activo' => true],
            ['nombre' => 'Llamada a padres / Tutor', 'descripcion' => 'Contacto formal con la familia', 'categoria' => 'meeting', 'severidad' => 'media', 'duracion_defecto_dias' => null, 'activo' => true],
            // Removed 'Reunión con orientación / psicólogo' per requirements
            ['nombre' => 'Suspensión temporal', 'descripcion' => 'Suspensión por días según normativa', 'categoria' => 'suspension', 'severidad' => 'alta', 'duracion_defecto_dias' => 3, 'activo' => true],
            ['nombre' => 'Expulsión', 'descripcion' => 'Expulsión (indicar desde cuándo)', 'categoria' => 'expulsion', 'severidad' => 'critica', 'duracion_defecto_dias' => null, 'activo' => true],
            // Removed 'Restitución o reparación del daño' per requirements
            ['nombre' => 'Multa / sanción económica', 'descripcion' => 'Sanción económica si aplica por normativa', 'categoria' => 'monetary', 'severidad' => 'alta', 'duracion_defecto_dias' => null, 'activo' => true],
        ];

        foreach ($tipos as $t) {
            DB::table('sancion_tipos')->insert(array_merge($t, ['created_at' => $now, 'updated_at' => $now]));
        }
    }
}
