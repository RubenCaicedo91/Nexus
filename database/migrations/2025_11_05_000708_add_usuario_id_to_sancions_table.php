<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ensure the table exists and the column doesn't already exist before adding it
        if (!Schema::hasTable('sancions')) {
            return;
        }

        if (!Schema::hasColumn('sancions', 'usuario_id')) {
            Schema::table('sancions', function (Blueprint $table) {
                $table->unsignedBigInteger('usuario_id')->nullable();
                // Si quieres agregar la relación con la tabla users, descomenta la siguiente línea:
                // $table->foreign('usuario_id')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('sancions')) {
            return;
        }

        Schema::table('sancions', function (Blueprint $table) {
            if (Schema::hasColumn('sancions', 'usuario_id')) {
                $table->dropColumn('usuario_id');
            }
        });
    }
};
