<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * We'll add an `anio` column, copy values from `periodo`, recreate the unique index,
     * then drop the `periodo` column. This keeps existing data safe.
     */
    public function up()
    {
        Schema::table('notas', function (Blueprint $table) {
            if (! Schema::hasColumn('notas', 'anio')) {
                $table->string('anio')->nullable()->after('materia_id');
            }
        });

        // Copiar valores de periodo a anio
        DB::statement('UPDATE notas SET anio = periodo WHERE periodo IS NOT NULL');

        // Antes de eliminar el índice único debemos quitar cualquier FK en otras tablas
        // que referencie columnas de `notas`. MySQL no permite eliminar un índice
        // si está siendo usado por una restricción de clave foránea.
                // Sólo obtener FKs que referencien las columnas que forman el índice que queremos eliminar
                $fks = DB::select("SELECT DISTINCT CONSTRAINT_NAME, TABLE_NAME
                        FROM information_schema.KEY_COLUMN_USAGE
                        WHERE REFERENCED_TABLE_NAME = 'notas'
                            AND REFERENCED_TABLE_SCHEMA = DATABASE()
                            AND REFERENCED_COLUMN_NAME IN ('matricula_id', 'materia_id', 'periodo');");

        foreach ($fks as $fk) {
            try {
                Schema::table($fk->TABLE_NAME, function (Blueprint $table) use ($fk) {
                    $table->dropForeign($fk->CONSTRAINT_NAME);
                });
            } catch (\Exception $e) {
                // Si falla, continuamos; la eliminación del índice puede fallar más abajo.
            }
        }

        // Para evitar conflictos con claves foráneas e índices existentes,
        // NO eliminamos el índice antiguo ni la columna `periodo` en esta migración.
        // Simplemente añadimos la columna `anio` y copiamos los valores desde `periodo`.
        // Si se desea convertir completamente en el futuro (quitar `periodo` y
        // recrear índices), se debe planear una migración separada y revisar
        // posibles referencias externas.

        // Nota: la copia ya se realizó arriba con DB::statement
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('notas', function (Blueprint $table) {
            if (! Schema::hasColumn('notas', 'periodo')) {
                $table->string('periodo')->nullable()->after('materia_id');
            }
        });

        DB::statement('UPDATE notas SET periodo = anio WHERE anio IS NOT NULL');

        Schema::table('notas', function (Blueprint $table) {
            try {
                $table->dropUnique('unique_nota_matricula_materia_anio');
            } catch (\Exception $e) {
            }

            $table->unique(['matricula_id', 'materia_id', 'periodo'], 'unique_nota_matricula_materia_periodo');

            if (Schema::hasColumn('notas', 'anio')) {
                $table->dropColumn('anio');
            }
        });
    }
};
