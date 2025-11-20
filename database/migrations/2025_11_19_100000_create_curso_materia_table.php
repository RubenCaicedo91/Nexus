<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::create('curso_materia', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('curso_id');
            $table->unsignedBigInteger('materia_id');
            $table->timestamps();

            $table->foreign('curso_id')->references('id')->on('cursos')->onDelete('cascade');
            $table->foreign('materia_id')->references('id')->on('materias')->onDelete('cascade');
            $table->unique(['curso_id','materia_id']);
        });

        // Migrar relaciones existentes desde materias.curso_id hacia la tabla pivote
        $materias = DB::table('materias')->select('id','curso_id')->get();
        foreach ($materias as $m) {
            if ($m->curso_id) {
                $exists = DB::table('curso_materia')
                    ->where('curso_id', $m->curso_id)
                    ->where('materia_id', $m->id)
                    ->exists();
                if (! $exists) {
                    DB::table('curso_materia')->insert([
                        'curso_id' => $m->curso_id,
                        'materia_id' => $m->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down()
    {
        Schema::dropIfExists('curso_materia');
    }
};
