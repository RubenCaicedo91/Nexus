<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Storage;
use App\Models\Matricula;

$disk = Storage::disk('ftp_matriculas');
$fields = ['documento_identidad','rh','certificado_medico','certificado_notas'];

$report = [];

Matricula::chunk(100, function($matriculas) use ($disk, $fields, &$report) {
    try {
        $allFiles = $disk->allFiles('estudiante');
    } catch (Exception $e) {
        echo "ERROR: no se pudo listar archivos FTP: " . $e->getMessage() . "\n";
        $allFiles = [];
    }

    // build map basename => list of paths
    $map = [];
    foreach ($allFiles as $p) {
        $b = strtolower(basename($p));
        if (! isset($map[$b])) $map[$b] = [];
        $map[$b][] = $p;
    }

    foreach ($matriculas as $m) {
        foreach ($fields as $f) {
            $val = $m->$f;
            if (! $val) continue;
            $exists = false;
            try { $exists = $disk->exists($val); } catch (Exception $e) { $exists = false; }

            if ($exists) {
                $report[] = [ 'matricula_id' => $m->id, 'campo' => $f, 'status' => 'EXISTS', 'path' => $val ];
                continue;
            }

            $basename = strtolower(basename($val));
            if (isset($map[$basename]) && count($map[$basename])>0) {
                $report[] = [ 'matricula_id' => $m->id, 'campo' => $f, 'status' => 'FOUND_BY_BASENAME', 'path' => $val, 'found_paths' => $map[$basename] ];
            } else {
                $report[] = [ 'matricula_id' => $m->id, 'campo' => $f, 'status' => 'MISSING', 'path' => $val ];
            }
        }
    }
});

// summarize
$counts = array_count_values(array_map(fn($r) => $r['status'], $report));

echo "Diagnóstico completado. Totales por estado:\n";
foreach ($counts as $k=>$v) echo " - $k: $v\n";

// print some examples of MISSING
$missing = array_filter($report, fn($r) => $r['status'] === 'MISSING');
$found = array_filter($report, fn($r) => $r['status'] === 'FOUND_BY_BASENAME');

if (count($missing)>0) {
    echo "\nEjemplos MISSING (hasta 10):\n";
    $i=0; foreach ($missing as $m) { if ($i++>9) break; echo json_encode($m, JSON_UNESCAPED_UNICODE) . "\n"; }
}

if (count($found)>0) {
    echo "\nEjemplos FOUND_BY_BASENAME (hasta 10):\n";
    $i=0; foreach ($found as $m) { if ($i++>9) break; echo json_encode($m, JSON_UNESCAPED_UNICODE) . "\n"; }
}

// Save report to file
$ts = date('Ymd_His');
$reportFile = __DIR__ . "/backups/matriculas_diagnostico_$ts.json";
file_put_contents($reportFile, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "\nReporte guardado en: $reportFile\n";
