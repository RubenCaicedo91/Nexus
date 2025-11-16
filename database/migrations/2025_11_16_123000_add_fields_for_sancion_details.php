<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('sancions', function (Blueprint $table) {
            $table->date('fecha_inicio')->nullable()->after('fecha');
            $table->date('fecha_fin')->nullable()->after('fecha_inicio');
            $table->decimal('monto', 10, 2)->nullable()->after('fecha_fin');
            $table->boolean('pago_obligatorio')->default(false)->after('monto');
            $table->text('pago_observacion')->nullable()->after('pago_obligatorio');
        });
    }

    public function down()
    {
        Schema::table('sancions', function (Blueprint $table) {
            $table->dropColumn(['fecha_inicio', 'fecha_fin', 'monto', 'pago_obligatorio', 'pago_observacion']);
        });
    }
};
