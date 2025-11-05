<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SancionesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
        ['nombre'=>'Amonestación verbal','descripcion'=>'Advertencia verbal','duracion_dias'=>null,'puntos'=>0],
        ['nombre'=>'Suspensión 1 día','descripcion'=>'Suspensión temporal','duracion_dias'=>1,'puntos'=>2],
        ['nombre'=>'Suspensión 3 días','descripcion'=>'Suspensión temporal mayor','duracion_dias'=>3,'puntos'=>5],
        ['nombre'=>'Expulsión temporal','descripcion'=>'Expulsión por gravedad','duracion_dias'=>null,'puntos'=>10],
    ];
    foreach ($data as $s) Sancion::updateOrCreate(['nombre'=>$s['nombre']], $s);
    }
}
