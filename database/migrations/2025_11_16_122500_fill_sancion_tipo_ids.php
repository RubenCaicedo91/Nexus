<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Intentar mapear valores existentes de sancions.tipo a sancion_tipos por nombre (case-insensitive)
        $tipos = DB::table('sancion_tipos')->select('id', 'nombre')->get();
        foreach ($tipos as $t) {
            DB::table('sancions')->whereRaw('LOWER(tipo) = ?', [mb_strtolower($t->nombre)])->update(['tipo_id' => $t->id]);
        }

        // Para los que no concuerden exactamente, intentar coincidencia parcial (LIKE)
        foreach ($tipos as $t) {
            DB::table('sancions')->whereNull('tipo_id')->where('tipo', 'like', "%{$t->nombre}%")->update(['tipo_id' => $t->id]);
        }
    }

    public function down()
    {
        DB::table('sancions')->update(['tipo_id' => null]);
    }
};
