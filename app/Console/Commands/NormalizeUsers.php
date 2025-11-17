<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class NormalizeUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:normalize-names {--force : Apply changes instead of dry-run} {--chunk=200 : Chunk size for processing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Normalize existing users.name into first_name, second_name, first_last, second_last. Use --force to apply changes.';

    public function handle()
    {
        $this->info('Iniciando proceso de normalización de usuarios (names → name parts)');

        $dryRun = ! $this->option('force');
        $chunk = (int) $this->option('chunk');

        $query = User::query()->orderBy('id');

        $total = $query->count();
        $this->info("Usuarios totales: $total");

        $changes = [];
        $processed = 0;

        $query->chunk($chunk, function($users) use (&$changes, &$processed, $dryRun) {
            foreach ($users as $user) {
                $processed++;

                // Saltar si ya tiene first_name y first_last definidos
                if (!empty($user->first_name) && !empty($user->first_last)) {
                    continue;
                }

                $name = trim($user->name ?? '');
                if ($name === '') continue;

                $tokens = preg_split('/\s+/', $name);
                $count = count($tokens);

                $new = [
                    'first_name' => $user->first_name,
                    'second_name' => $user->second_name,
                    'first_last' => $user->first_last,
                    'second_last' => $user->second_last,
                ];

                if ($count === 1) {
                    $new['first_name'] = $tokens[0];
                } elseif ($count === 2) {
                    $new['first_name'] = $tokens[0];
                    $new['first_last'] = $tokens[1];
                } elseif ($count === 3) {
                    $new['first_name'] = $tokens[0];
                    $new['second_name'] = $tokens[1];
                    $new['first_last'] = $tokens[2];
                } elseif ($count >= 4) {
                    $new['first_name'] = $tokens[0];
                    $new['second_name'] = $tokens[1];
                    $new['first_last'] = $tokens[$count - 2];
                    $new['second_last'] = $tokens[$count - 1];
                }

                $changed = [];
                foreach ($new as $k => $v) {
                    $old = $user->{$k} ?? null;
                    if (($old === null || $old === '') && !empty($v)) {
                        $changed[$k] = ['old' => $old, 'new' => $v];
                    }
                }

                if (!empty($changed)) {
                    $changes[] = [
                        'id' => $user->id,
                        'email' => $user->email,
                        'name' => $user->name,
                        'changed' => $changed,
                    ];

                    if (! $dryRun) {
                        // Aplicar cambios solo en campos vacíos para evitar sobrescribir datos manuales
                        $update = [];
                        foreach ($changed as $field => $diff) {
                            $update[$field] = $diff['new'];
                        }
                        $user->update($update);
                    }
                }
            }
        });

        $countChanges = count($changes);
        $this->info("Procesados: $processed. Registros con cambios sugeridos: $countChanges");

        if ($countChanges > 0) {
            // Guardar reporte en storage para revisión
            $ts = date('Ymd_His');
            $filename = "normalize_users_report_{$ts}.json";
            Storage::put("normalize/{$filename}", json_encode($changes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->info("Reporte guardado en storage: normalize/{$filename}");
        } else {
            $this->info('No se detectaron cambios a aplicar.');
        }

        if ($dryRun) {
            $this->info('Dry-run completado. Re-ejecuta con --force para aplicar los cambios.');
        } else {
            $this->info('Cambios aplicados correctamente.');
        }

        return 0;
    }
}
