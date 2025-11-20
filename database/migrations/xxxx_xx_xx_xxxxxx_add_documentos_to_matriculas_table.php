<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDocumentosToMatriculasTable extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('matriculas', 'documento_identidad') ||
            !Schema::hasColumn('matriculas', 'rh') ||
            !Schema::hasColumn('matriculas', 'certificado_medico') ||
            !Schema::hasColumn('matriculas', 'certificado_notas')) {
            Schema::table('matriculas', function (Blueprint $table) {
                if (!Schema::hasColumn('matriculas', 'documento_identidad')) {
                    $table->string('documento_identidad')->nullable();
                }
                if (!Schema::hasColumn('matriculas', 'rh')) {
                    $table->string('rh')->nullable();
                }
                if (!Schema::hasColumn('matriculas', 'certificado_medico')) {
                    $table->string('certificado_medico')->nullable();
                }
                if (!Schema::hasColumn('matriculas', 'certificado_notas')) {
                    $table->string('certificado_notas')->nullable();
                }
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('matriculas')) {
            Schema::table('matriculas', function (Blueprint $table) {
                $drop = [];
                if (Schema::hasColumn('matriculas', 'documento_identidad')) {
                    $drop[] = 'documento_identidad';
                }
                if (Schema::hasColumn('matriculas', 'rh')) {
                    $drop[] = 'rh';
                }
                if (Schema::hasColumn('matriculas', 'certificado_medico')) {
                    $drop[] = 'certificado_medico';
                }
                if (Schema::hasColumn('matriculas', 'certificado_notas')) {
                    $drop[] = 'certificado_notas';
                }
                if (!empty($drop)) {
                    $table->dropColumn($drop);
                }
            });
        }
    }
}
