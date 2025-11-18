<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSoloLecturaToNotificaciones extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('notificaciones', 'solo_lectura')) {
            Schema::table('notificaciones', function (Blueprint $table) {
                $table->boolean('solo_lectura')->default(false)->after('solo_acudiente_responde');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('notificaciones', 'solo_lectura')) {
            Schema::table('notificaciones', function (Blueprint $table) {
                $table->dropColumn('solo_lectura');
            });
        }
    }
}
