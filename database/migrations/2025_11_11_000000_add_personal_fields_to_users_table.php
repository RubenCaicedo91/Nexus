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
        Schema::table('users', function (Blueprint $table) {
            // Nombres y apellidos separados
            $table->string('first_name')->nullable()->after('name');
            $table->string('second_name')->nullable()->after('first_name');
            $table->string('first_last')->nullable()->after('second_name');
            $table->string('second_last')->nullable()->after('first_last');

            // Documento: tipo y número (tipo limitado a R.C, C.C, T.I) - permitimos null
            $table->string('document_type', 10)->nullable()->after('second_last');
            $table->string('document_number', 50)->nullable()->after('document_type');

            // Teléfono celular
            $table->string('celular', 30)->nullable()->after('document_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['first_name','second_name','first_last','second_last','document_type','document_number','celular']);
        });
    }
};
