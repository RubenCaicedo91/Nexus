<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Intento de clasificación automática basada en palabras clave en el nombre
        DB::table('sancion_tipos')->whereRaw("LOWER(nombre) LIKE '%suspens%' OR LOWER(nombre) LIKE '%suspensi%'")->update(['categoria' => 'suspension']);
        DB::table('sancion_tipos')->whereRaw("LOWER(nombre) LIKE '%multa%' OR LOWER(nombre) LIKE '%econ%' OR LOWER(nombre) LIKE '%sancion econ%'")->update(['categoria' => 'monetary']);
        // El resto queda como 'normal'
        DB::table('sancion_tipos')->whereNull('categoria')->update(['categoria' => 'normal']);
    }

    public function down()
    {
        DB::table('sancion_tipos')->update(['categoria' => null]);
    }
};
