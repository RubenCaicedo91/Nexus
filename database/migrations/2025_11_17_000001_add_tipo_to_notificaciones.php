<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTipoToNotificaciones extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('notificaciones', 'tipo')) {
            Schema::table('notificaciones', function (Blueprint $table) {
                $table->string('tipo')->nullable()->after('creador_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('notificaciones', 'tipo')) {
            Schema::table('notificaciones', function (Blueprint $table) {
                $table->dropColumn('tipo');
            });
        }
    }
}
