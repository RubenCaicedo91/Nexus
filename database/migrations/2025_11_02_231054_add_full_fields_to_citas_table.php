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
        Schema::table('citas', function (Blueprint $table) {
            // Agregar relaciones faltantes
            $table->foreignId('solicitante_id')->after('id')->constrained('users')->onDelete('cascade');
            $table->foreignId('orientador_id')->after('solicitante_id')->constrained('users')->onDelete('cascade');
            
            // Cambiar nombre y hacer nullable estudiante_id
            $table->dropColumn('estudiante_id');
            $table->foreignId('estudiante_referido_id')->after('orientador_id')->nullable()->constrained('users')->onDelete('cascade');
            
            // Información de la cita
            $table->string('tipo_cita')->after('estudiante_referido_id')->default('orientacion');
            $table->string('modalidad')->after('tipo_cita')->default('presencial');
            $table->string('motivo')->after('modalidad');
            $table->text('descripcion')->after('motivo')->nullable();
            $table->text('observaciones_previas')->after('descripcion')->nullable();
            
            // Cambiar fecha por campos separados
            $table->dropColumn('fecha');
            $table->date('fecha_solicitada')->after('observaciones_previas');
            $table->time('hora_solicitada')->after('fecha_solicitada');
            $table->date('fecha_asignada')->after('hora_solicitada')->nullable();
            $table->time('hora_asignada')->after('fecha_asignada')->nullable();
            $table->integer('duracion_estimada')->after('hora_asignada')->default(30);
            
            // Cambiar enum de estado
            $table->dropColumn('estado');
            $table->enum('estado', [
                'solicitada',
                'programada', 
                'confirmada',
                'en_curso',
                'completada',
                'cancelada',
                'reprogramada'
            ])->after('duracion_estimada')->default('solicitada');
            
            $table->enum('prioridad', ['baja', 'media', 'alta', 'urgente'])->after('estado')->default('media');
            
            // Resultados de la cita
            $table->text('resumen_cita')->after('prioridad')->nullable();
            $table->text('recomendaciones')->after('resumen_cita')->nullable();
            $table->text('plan_seguimiento')->after('recomendaciones')->nullable();
            $table->boolean('requiere_seguimiento')->after('plan_seguimiento')->default(false);
            $table->date('fecha_seguimiento')->after('requiere_seguimiento')->nullable();
            
            // Metadatos
            $table->string('lugar_cita')->after('fecha_seguimiento')->nullable();
            $table->string('link_virtual')->after('lugar_cita')->nullable();
            $table->text('instrucciones_adicionales')->after('link_virtual')->nullable();
            
            // Cancelación
            $table->text('motivo_cancelacion')->after('instrucciones_adicionales')->nullable();
            $table->timestamp('fecha_cancelacion')->after('motivo_cancelacion')->nullable();
            $table->foreignId('cancelado_por')->after('fecha_cancelacion')->nullable()->constrained('users');
            
            // Índices
            $table->index(['fecha_asignada', 'hora_asignada']);
            $table->index(['estado', 'prioridad']);
            $table->index(['orientador_id', 'fecha_asignada']);
            $table->index(['solicitante_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            // Revertir cambios
            $table->dropForeign(['solicitante_id']);
            $table->dropForeign(['orientador_id']);
            $table->dropForeign(['estudiante_referido_id']);
            $table->dropForeign(['cancelado_por']);
            
            $table->dropColumn([
                'solicitante_id', 'orientador_id', 'estudiante_referido_id',
                'tipo_cita', 'modalidad', 'motivo', 'descripcion', 'observaciones_previas',
                'fecha_solicitada', 'hora_solicitada', 'fecha_asignada', 'hora_asignada', 'duracion_estimada',
                'estado', 'prioridad', 'resumen_cita', 'recomendaciones', 'plan_seguimiento',
                'requiere_seguimiento', 'fecha_seguimiento', 'lugar_cita', 'link_virtual',
                'instrucciones_adicionales', 'motivo_cancelacion', 'fecha_cancelacion', 'cancelado_por'
            ]);
            
            // Restaurar campos originales
            $table->bigInteger('estudiante_id')->unsigned();
            $table->datetime('fecha');
            $table->enum('estado', ['pendiente','agendada','atendida'])->default('pendiente');
        });
    }
};
