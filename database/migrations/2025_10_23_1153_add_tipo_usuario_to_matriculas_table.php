<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTipoUsuarioToMatriculasTable extends Migration
{
    public function up()
    {
        Schema::table('matriculas', function (Blueprint $table) {
            $table->string('tipo_usuario')->nullable()->after('estado');
        });
    }

    public function down()
    {
        Schema::table('matriculas', function (Blueprint $table) {
            $table->dropColumn('tipo_usuario');
        });
    }
}
