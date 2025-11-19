<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Eliminar tipos que el usuario pidió quitar
        DB::table('sancion_tipos')->whereIn('nombre', [
            'Restitución o reparación del daño',
            'Reunión con orientación / psicólogo'
        ])->delete();
    }

    public function down()
    {
        // No recreamos automáticamente esas filas en down
    }
};
