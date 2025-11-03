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
        Schema::create('notas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matricula_id')->constrained('matriculas')->onDelete('cascade');
            $table->foreignId('materia_id')->constrained('materias')->onDelete('cascade');
            $table->string('periodo')->nullable();
            $table->decimal('valor', 5, 2)->default(0);
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->unique(['matricula_id', 'materia_id', 'periodo'], 'unique_nota_matricula_materia_periodo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notas');
    }
};
