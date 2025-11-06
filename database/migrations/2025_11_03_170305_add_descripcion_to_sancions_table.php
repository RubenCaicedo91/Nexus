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
        // Only add the columns if the table exists and the columns don't already exist.
        if (!Schema::hasTable('sancions')) {
            return;
        }

        // Add columns individually if they are missing to avoid duplicate column errors
        if (!Schema::hasColumn('sancions', 'descripcion') || !Schema::hasColumn('sancions', 'tipo')) {
            Schema::table('sancions', function (Blueprint $table) {
                if (!Schema::hasColumn('sancions', 'descripcion')) {
                    $table->string('descripcion')->nullable();
                }
                if (!Schema::hasColumn('sancions', 'tipo')) {
                    $table->string('tipo')->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
{
    Schema::table('sancions', function (Blueprint $table) {
        if (Schema::hasColumn('sancions', 'descripcion')) {
            $table->dropColumn('descripcion');
        }
        if (Schema::hasColumn('sancions', 'tipo')) {
            $table->dropColumn('tipo');
        }
    });
}
};
