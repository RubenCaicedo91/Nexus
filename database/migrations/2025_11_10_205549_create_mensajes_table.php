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
        Schema::create('mensajes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('remitente_id');    // Usuario que envía
            $table->unsignedBigInteger('destinatario_id'); // Usuario que recibe
            $table->string('asunto');                      // Asunto del mensaje
            $table->text('contenido');                     // Contenido del mensaje
            $table->boolean('leido')->default(false);      // Estado de lectura
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mensajes');
    }
};
