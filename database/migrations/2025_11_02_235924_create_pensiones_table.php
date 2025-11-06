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
        Schema::create('pensiones', function (Blueprint $table) {
            $table->id();
            
            // Relaciones
            $table->foreignId('estudiante_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('acudiente_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('curso_id')->nullable()->constrained('cursos')->onDelete('set null');
            
            // Información de la pensión
            $table->string('concepto', 200); // Mensualidad, matrícula, etc.
            $table->text('descripcion')->nullable();
            $table->decimal('valor_base', 10, 2);
            $table->decimal('descuentos', 10, 2)->default(0);
            $table->decimal('recargos', 10, 2)->default(0);
            $table->decimal('valor_total', 10, 2);
            
            // Fechas
            $table->date('fecha_vencimiento');
            $table->date('fecha_generacion');
            $table->date('fecha_pago')->nullable();
            $table->integer('mes_correspondiente'); // 1-12
            $table->integer('año_correspondiente');
            
            // Estado y pagos
            $table->enum('estado', ['pendiente', 'pagada', 'vencida', 'parcial', 'anulada'])->default('pendiente');
            $table->enum('tipo_pension', ['mensualidad', 'matricula', 'otros_pagos', 'uniformes', 'alimentacion', 'transporte'])->default('mensualidad');
            
            // Información de pago
            $table->string('numero_factura')->unique()->nullable();
            $table->string('metodo_pago')->nullable(); // efectivo, transferencia, tarjeta
            $table->string('referencia_pago')->nullable();
            $table->decimal('valor_pagado', 10, 2)->default(0);
            $table->text('observaciones_pago')->nullable();
            
            // Gestión de cobros
            $table->integer('dias_mora')->default(0);
            $table->decimal('valor_mora', 10, 2)->default(0);
            $table->boolean('notificacion_enviada')->default(false);
            $table->date('fecha_notificacion')->nullable();
            $table->integer('intentos_cobro')->default(0);
            
            // Archivos y documentos
            $table->string('comprobante_pago')->nullable();
            $table->json('documentos_adjuntos')->nullable();
            
            // Metadatos
            $table->foreignId('procesado_por')->nullable()->constrained('users')->onDelete('set null'); // Usuario que procesó el pago
            $table->text('notas_internas')->nullable();
            $table->boolean('genera_certificado')->default(false);
            
            $table->timestamps();
            
            // Índices para mejorar rendimiento
            $table->index(['estudiante_id', 'estado']);
            $table->index(['acudiente_id', 'estado']);
            $table->index(['fecha_vencimiento']);
            $table->index(['estado', 'fecha_vencimiento']);
            $table->index(['año_correspondiente', 'mes_correspondiente']);
            $table->unique(['estudiante_id', 'año_correspondiente', 'mes_correspondiente', 'tipo_pension'], 'unique_pension_estudiante_periodo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pensiones');
    }
};
