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
        Schema::table('circulars', function (Blueprint $table) {
            $table->unsignedBigInteger('creador_id')->nullable()->after('archivo');
            $table->foreign('creador_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('circulars', function (Blueprint $table) {
            $table->dropForeign(['creador_id']);
            $table->dropColumn('creador_id');
        });
    }
};
