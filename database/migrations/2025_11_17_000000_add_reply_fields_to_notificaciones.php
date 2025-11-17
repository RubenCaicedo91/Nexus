<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('notificaciones', function (Blueprint $table) {
            if (! Schema::hasColumn('notificaciones', 'creador_id')) {
                $table->unsignedBigInteger('creador_id')->nullable()->after('usuario_id')->index();
            }
            if (! Schema::hasColumn('notificaciones', 'solo_acudiente_responde')) {
                $table->boolean('solo_acudiente_responde')->default(false)->after('fecha');
            }
        });
    }

    public function down()
    {
        Schema::table('notificaciones', function (Blueprint $table) {
            if (Schema::hasColumn('notificaciones', 'solo_acudiente_responde')) {
                $table->dropColumn('solo_acudiente_responde');
            }
            if (Schema::hasColumn('notificaciones', 'creador_id')) {
                $table->dropColumn('creador_id');
            }
        });
    }
};
