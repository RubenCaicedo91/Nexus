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
        Schema::table('pensiones', function (Blueprint $table) {
            if (Schema::hasColumn('pensiones', 'mes')) {
                $table->dropColumn('mes');
            }
            if (Schema::hasColumn('pensiones', 'año')) {
                $table->dropColumn('año');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pensiones', function (Blueprint $table) {
            if (! Schema::hasColumn('pensiones', 'mes')) {
                $table->integer('mes')->nullable()->after('fecha_generacion');
            }
            if (! Schema::hasColumn('pensiones', 'año')) {
                $table->integer('año')->nullable()->after('mes');
            }
        });
    }
};
