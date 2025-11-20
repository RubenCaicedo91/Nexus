<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('notas', function (Blueprint $table) {
            if (! Schema::hasColumn('notas', 'definitiva')) {
                $table->boolean('definitiva')->default(false)->after('aprobada');
            }
            if (! Schema::hasColumn('notas', 'definitiva_por')) {
                $table->unsignedBigInteger('definitiva_por')->nullable()->after('definitiva');
                $table->foreign('definitiva_por')->references('id')->on('users')->onDelete('set null');
            }
            if (! Schema::hasColumn('notas', 'definitiva_en')) {
                $table->dateTime('definitiva_en')->nullable()->after('definitiva_por');
            }
        });
    }

    public function down()
    {
        Schema::table('notas', function (Blueprint $table) {
            if (Schema::hasColumn('notas', 'definitiva_por')) {
                $table->dropForeign(['definitiva_por']);
                $table->dropColumn('definitiva_por');
            }
            if (Schema::hasColumn('notas', 'definitiva')) {
                $table->dropColumn('definitiva');
            }
            if (Schema::hasColumn('notas', 'definitiva_en')) {
                $table->dropColumn('definitiva_en');
            }
        });
    }
};
