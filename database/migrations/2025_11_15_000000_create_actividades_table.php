<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('actividades', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('nota_id');
            $table->string('nombre');
            $table->float('valor', 5, 2)->default(0); // escala 0.00 - 5.00
            $table->timestamps();

            $table->foreign('nota_id')->references('id')->on('notas')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('actividades');
    }
};
