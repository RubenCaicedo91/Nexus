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
        Schema::table('mensajes', function (Blueprint $table) {
            $table->boolean('deleted_by_remitente')->default(false)->after('leido');
            $table->boolean('deleted_by_destinatario')->default(false)->after('deleted_by_remitente');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mensajes', function (Blueprint $table) {
            $table->dropColumn(['deleted_by_remitente', 'deleted_by_destinatario']);
        });
    }
};
