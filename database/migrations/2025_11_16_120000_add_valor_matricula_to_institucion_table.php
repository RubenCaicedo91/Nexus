<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('institucion', function (Blueprint $table) {
            if (! Schema::hasColumn('institucion', 'valor_matricula')) {
                $table->decimal('valor_matricula', 12, 2)->nullable()->default(0);
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('institucion', function (Blueprint $table) {
            if (Schema::hasColumn('institucion', 'valor_matricula')) {
                $table->dropColumn('valor_matricula');
            }
        });
    }
};
