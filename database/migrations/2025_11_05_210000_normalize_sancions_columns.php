<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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

        // descripcion -> nullable
        if (Schema::hasColumn('sancions', 'descripcion')) {
            try {
                Schema::table('sancions', function (Blueprint $table) {
                    $table->string('descripcion')->nullable()->change();
                });
            } catch (\Throwable $e) {
                // Fallback to raw SQL if doctrine/dbal is not available
                DB::statement('ALTER TABLE `sancions` MODIFY `descripcion` VARCHAR(255) NULL');
            }
        }

        // tipo -> nullable
        if (Schema::hasColumn('sancions', 'tipo')) {
            try {
                Schema::table('sancions', function (Blueprint $table) {
                    $table->string('tipo')->nullable()->change();
                });
            } catch (\Throwable $e) {
                DB::statement('ALTER TABLE `sancions` MODIFY `tipo` VARCHAR(255) NULL');
            }
        }

        // fecha -> nullable
        if (Schema::hasColumn('sancions', 'fecha')) {
            try {
                Schema::table('sancions', function (Blueprint $table) {
                    $table->date('fecha')->nullable()->change();
                });
            } catch (\Throwable $e) {
                DB::statement('ALTER TABLE `sancions` MODIFY `fecha` DATE NULL');
            }
        }

        // usuario_id -> nullable
        if (Schema::hasColumn('sancions', 'usuario_id')) {
            try {
                Schema::table('sancions', function (Blueprint $table) {
                    $table->unsignedBigInteger('usuario_id')->nullable()->change();
                });
            } catch (\Throwable $e) {
                DB::statement('ALTER TABLE `sancions` MODIFY `usuario_id` BIGINT UNSIGNED NULL');
            }
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

        // Revert changes: set columns NOT NULL (use with caution)
        if (Schema::hasColumn('sancions', 'descripcion')) {
            try {
                Schema::table('sancions', function (Blueprint $table) {
                    $table->string('descripcion')->nullable(false)->change();
                });
            } catch (\Throwable $e) {
                DB::statement('ALTER TABLE `sancions` MODIFY `descripcion` VARCHAR(255) NOT NULL');
            }
        }

        if (Schema::hasColumn('sancions', 'tipo')) {
            try {
                Schema::table('sancions', function (Blueprint $table) {
                    $table->string('tipo')->nullable(false)->change();
                });
            } catch (\Throwable $e) {
                DB::statement('ALTER TABLE `sancions` MODIFY `tipo` VARCHAR(255) NOT NULL');
            }
        }

        if (Schema::hasColumn('sancions', 'fecha')) {
            try {
                Schema::table('sancions', function (Blueprint $table) {
                    $table->date('fecha')->nullable(false)->change();
                });
            } catch (\Throwable $e) {
                DB::statement('ALTER TABLE `sancions` MODIFY `fecha` DATE NOT NULL');
            }
        }

        if (Schema::hasColumn('sancions', 'usuario_id')) {
            try {
                Schema::table('sancions', function (Blueprint $table) {
                    $table->unsignedBigInteger('usuario_id')->nullable(false)->change();
                });
            } catch (\Throwable $e) {
                DB::statement('ALTER TABLE `sancions` MODIFY `usuario_id` BIGINT UNSIGNED NOT NULL');
            }
        }
    }
};
