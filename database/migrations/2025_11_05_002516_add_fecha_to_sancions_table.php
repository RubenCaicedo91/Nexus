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
        if (!Schema::hasTable('sancions')) {
            return;
        }

        if (!Schema::hasColumn('sancions', 'fecha')) {
            Schema::table('sancions', function (Blueprint $table) {
                $table->date('fecha')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('sancions')) {
            return;
        }

        if (Schema::hasColumn('sancions', 'fecha')) {
            Schema::table('sancions', function (Blueprint $table) {
                $table->dropColumn('fecha');
            });
        }
    }
};
