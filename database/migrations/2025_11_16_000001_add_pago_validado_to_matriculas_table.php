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
            $table->boolean('pago_validado')->default(false)->after('fecha_pago');
            $table->unsignedBigInteger('pago_validado_por')->nullable()->after('pago_validado');
            $table->timestamp('pago_validado_at')->nullable()->after('pago_validado_por');

            $table->index('pago_validado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('matriculas', function (Blueprint $table) {
            $table->dropIndex(['pago_validado']);
            $table->dropColumn(['pago_validado', 'pago_validado_por', 'pago_validado_at']);
        });
    }
};
