<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('notificaciones', function (Blueprint $table) {
            if (! Schema::hasColumn('notificaciones', 'group_key')) {
                $table->string('group_key')->nullable()->after('creador_id')->index();
            }
            if (! Schema::hasColumn('notificaciones', 'deleted_by_creador')) {
                $table->boolean('deleted_by_creador')->default(false)->after('solo_acudiente_responde');
            }
        });

        Schema::table('mensajes', function (Blueprint $table) {
            if (! Schema::hasColumn('mensajes', 'notificacion_id')) {
                $table->unsignedBigInteger('notificacion_id')->nullable()->after('parent_id')->index();
            }
        });
    }

    public function down()
    {
        Schema::table('notificaciones', function (Blueprint $table) {
            if (Schema::hasColumn('notificaciones', 'deleted_by_creador')) {
                $table->dropColumn('deleted_by_creador');
            }
            if (Schema::hasColumn('notificaciones', 'group_key')) {
                $table->dropColumn('group_key');
            }
        });

        Schema::table('mensajes', function (Blueprint $table) {
            if (Schema::hasColumn('mensajes', 'notificacion_id')) {
                $table->dropColumn('notificacion_id');
            }
        });
    }
};
