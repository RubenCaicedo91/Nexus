<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('sancions', function (Blueprint $table) {
            $table->unsignedBigInteger('tipo_id')->nullable()->after('tipo');
            $table->foreign('tipo_id')->references('id')->on('sancion_tipos')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('sancions', function (Blueprint $table) {
            $table->dropForeign(['tipo_id']);
            $table->dropColumn('tipo_id');
        });
    }
};
