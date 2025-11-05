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
        Schema::create('reportes_disciplinarios', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // estudiante afectado
        $table->foreignId('reporter_id')->nullable()->constrained('users')->nullOnDelete(); // quien reporta
        $table->unsignedBigInteger('curso_id')->nullable();
        // si ya tienes tabla cursos, usa: $table->foreignId('curso_id')->nullable()->constrained('cursos')->nullOnDelete();
        $table->dateTime('fecha_incidencia');
        $table->text('descripcion');
        $table->enum('gravedad', ['baja','media','alta'])->default('baja');
        $table->enum('estado', ['abierto','investigando','resuelto','archivado'])->default('abierto');
        $table->foreignId('sancion_id')->nullable()->constrained('sanciones')->nullOnDelete();
        $table->json('evidencia')->nullable(); // array de rutas
        $table->timestamps();

        $table->index(['user_id','estado','fecha_incidencia']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reporte_disciplinarios');
    }
};
