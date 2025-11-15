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
        Schema::table('horarios', function (Blueprint $table) {
            $table->time('hora_fin')->nullable()->after('hora');
            $table->unsignedBigInteger('materia_id')->nullable()->after('hora_fin');
            $table->index('materia_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('horarios', function (Blueprint $table) {
            $table->dropIndex(['materia_id']);
            $table->dropColumn('materia_id');
            $table->dropColumn('hora_fin');
        });
    }
};
