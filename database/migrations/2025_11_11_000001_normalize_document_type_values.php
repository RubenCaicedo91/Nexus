<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Normalizar document_type en usuarios (eliminar puntos y espacios, pasar a mayúsculas)
        DB::table('users')
            ->whereNotNull('document_type')
            ->update([
                'document_type' => DB::raw("UPPER(REPLACE(REPLACE(document_type, '.', ''), ' ', ''))")
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reversible: este migration normaliza valores y no conserva el formato original.
    }
};
