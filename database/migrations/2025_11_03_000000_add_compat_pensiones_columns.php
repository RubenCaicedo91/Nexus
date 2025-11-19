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
        Schema::table('pensiones', function (Blueprint $table) {
            if (! Schema::hasColumn('pensiones', 'grado')) {
                $table->string('grado')->nullable()->after('curso_id');
            }

            if (! Schema::hasColumn('pensiones', 'mes')) {
                $table->integer('mes')->nullable()->after('concepto');
            }

            if (! Schema::hasColumn('pensiones', 'año')) {
                $table->integer('año')->nullable()->after('mes');
            }

            if (! Schema::hasColumn('pensiones', 'recargo_mora')) {
                $table->decimal('recargo_mora', 10, 2)->default(0)->after('recargos');
            }

            if (! Schema::hasColumn('pensiones', 'fecha_recargo')) {
                $table->dateTime('fecha_recargo')->nullable()->after('recargo_mora');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pensiones', function (Blueprint $table) {
            if (Schema::hasColumn('pensiones', 'grado')) {
                $table->dropColumn('grado');
            }
            if (Schema::hasColumn('pensiones', 'mes')) {
                $table->dropColumn('mes');
            }
            if (Schema::hasColumn('pensiones', 'año')) {
                $table->dropColumn('año');
            }
            if (Schema::hasColumn('pensiones', 'recargo_mora')) {
                $table->dropColumn('recargo_mora');
            }
            if (Schema::hasColumn('pensiones', 'fecha_recargo')) {
                $table->dropColumn('fecha_recargo');
            }
        });
    }
};
