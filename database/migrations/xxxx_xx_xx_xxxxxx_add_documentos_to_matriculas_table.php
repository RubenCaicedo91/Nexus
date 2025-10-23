<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDocumentosToMatriculasTable extends Migration
{
    public function up()
    {
        Schema::table('matriculas', function (Blueprint $table) {
            $table->string('documento_identidad')->nullable()->after('tipo_usuario');
            $table->string('rh')->nullable()->after('documento_identidad');
            $table->string('certificado_medico')->nullable()->after('rh');
            $table->string('certificado_notas')->nullable()->after('certificado_medico');
        });
    }

    public function down()
    {
        Schema::table('matriculas', function (Blueprint $table) {
            $table->dropColumn(['documento_identidad', 'rh', 'certificado_medico', 'certificado_notas']);
        });
    }
}
