<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('sancions', function (Blueprint $table) {
            $table->dateTime('reunion_at')->nullable()->after('monto');
        });
    }

    public function down()
    {
        Schema::table('sancions', function (Blueprint $table) {
            $table->dropColumn('reunion_at');
        });
    }
};
