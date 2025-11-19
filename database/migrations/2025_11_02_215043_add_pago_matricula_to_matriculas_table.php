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
        if (!Schema::hasTable('matriculas')) {
            return;
        }

        Schema::table('matriculas', function (Blueprint $table) {
            if (!Schema::hasColumn('matriculas', 'comprobante_pago')) {
                $table->string('comprobante_pago')->nullable();
            }
            if (!Schema::hasColumn('matriculas', 'monto_pago')) {
                $table->decimal('monto_pago', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('matriculas', 'fecha_pago')) {
                $table->date('fecha_pago')->nullable();
            }
            if (!Schema::hasColumn('matriculas', 'documentos_completos')) {
                $table->boolean('documentos_completos')->default(false);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('matriculas')) {
            return;
        }

        Schema::table('matriculas', function (Blueprint $table) {
            $drop = [];
            if (Schema::hasColumn('matriculas', 'comprobante_pago')) {
                $drop[] = 'comprobante_pago';
            }
            if (Schema::hasColumn('matriculas', 'monto_pago')) {
                $drop[] = 'monto_pago';
            }
            if (Schema::hasColumn('matriculas', 'fecha_pago')) {
                $drop[] = 'fecha_pago';
            }
            if (Schema::hasColumn('matriculas', 'documentos_completos')) {
                $drop[] = 'documentos_completos';
            }
            if (!empty($drop)) {
                $table->dropColumn($drop);
            }
        });
    }
};
