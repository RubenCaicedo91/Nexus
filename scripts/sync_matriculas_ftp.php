<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Storage;
use App\Models\Matricula;

$disk = Storage::disk('ftp_matriculas');
$fields = ['documento_identidad','rh','certificado_medico','certificado_notas'];

$backupDir = __DIR__ . '/backups';
if (! is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}
$ts = date('Ymd_His');
$backupFile = "$backupDir/matriculas_backup_$ts.csv";
$logFile = "$backupDir/matriculas_sync_log_$ts.txt";

$fh = fopen($backupFile, 'w');
if (! $fh) {
    echo "No se pudo crear backup CSV\n";
    exit(1);
}
// Header
fputcsv($fh, ['matricula_id','campo','ruta_original','ruta_encontrada','actualizado']);

$log = fopen($logFile, 'w');
if (! $log) {
    echo "No se pudo crear log\n";
    fclose($fh);
    exit(1);
}

fwrite($log, "Iniciando sincronización de matrículas con FTP - $ts\n");

// Listar todos los archivos bajo 'estudiante' una sola vez (cache)
try {
    fwrite($log, "Obteniendo lista de archivos desde FTP (estudiante/) ...\n");
    $allFiles = $disk->allFiles('estudiante');
    fwrite($log, "Cantidad de archivos en FTP bajo 'estudiante': " . count($allFiles) . "\n");
} catch (Exception $e) {
    fwrite($log, "ERROR: no se pudo listar archivos FTP: " . $e->getMessage() . "\n");
    echo "ERROR: no se pudo listar archivos FTP: " . $e->getMessage() . "\n";
    fclose($fh);
    fclose($log);
    exit(1);
}

// Construir mapa basename -> ruta (tomar la primera aparición)
$map = [];
foreach ($allFiles as $p) {
    $b = strtolower(basename($p));
    if (! isset($map[$b])) {
        $map[$b] = $p;
    }
}

$updatedCount = 0;
$checkedCount = 0;

Matricula::chunk(100, function($matriculas) use (&$map, &$fh, &$log, &$updatedCount, &$checkedCount, $fields, $disk) {
    foreach ($matriculas as $mat) {
        foreach ($fields as $field) {
            $val = $mat->$field;
            if (! $val) continue;
            $checkedCount++;
            // Si ya existe en disco exacta, nada que hacer
            try {
                if ($disk->exists($val)) {
                    // Registrar en backup como no cambiado
                    fputcsv($fh, [$mat->id, $field, $val, $val, 'NO']);
                    continue;
                }
            } catch (Exception $e) {
                // Problema con exists - registrar y saltar
                fwrite($log, "WARN: error exists() para matricula {$mat->id} campo {$field}: " . $e->getMessage() . "\n");
                fputcsv($fh, [$mat->id, $field, $val, '', 'ERROR_EXISTS']);
                continue;
            }

            $basename = strtolower(basename($val));
            if (isset($map[$basename])) {
                $found = $map[$basename];
                // Actualizar modelo con la ruta encontrada
                $old = $val;
                $mat->$field = $found;
                try {
                    $mat->save();
                    $updatedCount++;
                    fputcsv($fh, [$mat->id, $field, $old, $found, 'YES']);
                    fwrite($log, "UPDATED matricula {$mat->id} campo {$field}: $old => $found\n");
                } catch (Exception $e) {
                    fputcsv($fh, [$mat->id, $field, $old, $found, 'ERROR_SAVE']);
                    fwrite($log, "ERROR saving matricula {$mat->id} campo {$field}: " . $e->getMessage() . "\n");
                }
            } else {
                // No encontrado
                fputcsv($fh, [$mat->id, $field, $val, '', 'NO_MATCH']);
            }
        }
    }
});

fwrite($log, "Sincronización finalizada. Registradas: $checkedCount campos, actualizados: $updatedCount\n");
fclose($fh);
fclose($log);

echo "Hecho. Backup: $backupFile\nLog: $logFile\nCampos revisados: $checkedCount. Actualizados: $updatedCount\n";
