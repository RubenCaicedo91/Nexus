<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Usamos sentencia SQL directa para evitar dependencia de doctrine/dbal
        DB::statement('ALTER TABLE `citas` MODIFY `orientador_id` BIGINT UNSIGNED NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Volver a NOT NULL (puede fallar si existen nulos)
        DB::statement('ALTER TABLE `citas` MODIFY `orientador_id` BIGINT UNSIGNED NOT NULL');
    }
};
