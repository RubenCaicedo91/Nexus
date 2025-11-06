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
        Schema::table('notas', function (Blueprint $table) {
            if (! Schema::hasColumn('notas', 'aprobada')) {
                $table->boolean('aprobada')->default(false)->after('valor');
            }
            if (! Schema::hasColumn('notas', 'aprobado_por')) {
                $table->unsignedBigInteger('aprobado_por')->nullable()->after('aprobada');
                $table->foreign('aprobado_por')->references('id')->on('users')->onDelete('set null');
            }
            if (! Schema::hasColumn('notas', 'aprobado_en')) {
                $table->dateTime('aprobado_en')->nullable()->after('aprobado_por');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notas', function (Blueprint $table) {
            if (Schema::hasColumn('notas', 'aprobado_por')) {
                $table->dropForeign(['aprobado_por']);
                $table->dropColumn('aprobado_por');
            }
            if (Schema::hasColumn('notas', 'aprobada')) {
                $table->dropColumn('aprobada');
            }
            if (Schema::hasColumn('notas', 'aprobado_en')) {
                $table->dropColumn('aprobado_en');
            }
        });
    }
};
