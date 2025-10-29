<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Matricula;
use Illuminate\Support\Facades\Storage;

$campos = ['documento_identidad','rh','certificado_medico','certificado_notas'];
$any=false;
foreach (Matricula::all() as $mat) {
    $has=false;
    foreach ($campos as $c) { if ($mat->$c) { $has=true; break; } }
    if (!$has) continue;
    $any=true;
    echo "Matricula id={$mat->id}\n";
    foreach ($campos as $c) {
        $v = $mat->$c;
        echo "  $c: "; var_export($v); echo "\n";
        if ($v) {
            $disk = Storage::disk('ftp_matriculas');
            $exists = $disk->exists($v) ? 'YES' : 'NO';
            echo "    exists? $exists\n";
        }
    }
}
if (!$any) echo "No se encontraron archivos en matriculas.\n";
