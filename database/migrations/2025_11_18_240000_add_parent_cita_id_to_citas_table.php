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
        Schema::table('citas', function (Blueprint $table) {
            if (! Schema::hasColumn('citas', 'parent_cita_id')) {
                $table->foreignId('parent_cita_id')->nullable()->after('id')->constrained('citas')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            if (Schema::hasColumn('citas', 'parent_cita_id')) {
                $table->dropForeign(['parent_cita_id']);
                $table->dropColumn('parent_cita_id');
            }
        });
    }
};
