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
        Schema::table('matriculas', function (Blueprint $table) {
            $table->string('comprobante_pago')->nullable()->after('certificado_notas');
            $table->decimal('monto_pago', 10, 2)->nullable()->after('comprobante_pago');
            $table->date('fecha_pago')->nullable()->after('monto_pago');
            $table->boolean('documentos_completos')->default(false)->after('fecha_pago');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('matriculas', function (Blueprint $table) {
            $table->dropColumn(['comprobante_pago', 'monto_pago', 'fecha_pago', 'documentos_completos']);
        });
    }
};
