<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Storage;

$id = $argv[1] ?? 2;
$m = App\Models\Matricula::find($id);
if (! $m) {
    echo "Matricula id=$id NOT FOUND\n";
    exit(1);
}

echo "Matricula id={$m->id}\n";
echo " user_id={$m->user_id}\n";
echo " documento_identidad=" . var_export($m->documento_identidad, true) . "\n";
echo " rh=" . var_export($m->rh, true) . "\n";
echo " certificado_medico=" . var_export($m->certificado_medico, true) . "\n";
echo " certificado_notas=" . var_export($m->certificado_notas, true) . "\n";
echo " comprobante_pago=" . var_export($m->comprobante_pago, true) . "\n";

$disk = Storage::disk('ftp_matriculas');

$fields = ['documento_identidad','rh','certificado_medico','certificado_notas','comprobante_pago'];

foreach ($fields as $f) {
    $path = $m->$f;
    if (! $path) {
        echo "- Field $f: EMPTY\n";
        continue;
    }
    echo "- Field $f: path='$path'\n";
    try {
        $exists = $disk->exists($path) ? 'YES' : 'NO';
    } catch (Exception $e) {
        $exists = 'ERROR: ' . $e->getMessage();
    }
    echo "    disk->exists: $exists\n";
    echo "    basename: " . basename($path) . "\n";
}

// List files under estudiante/<slug> if slug available
try {
    $slug = null;
    if (! empty($m->user_id)) {
        $user = App\Models\User::find($m->user_id);
        if ($user) {
            $slug = preg_replace('/[^A-Za-z0-9_\-]/', '_', strtolower(trim($user->name))) . '_' . $user->id;
        }
    }
    $baseDir = $slug ? ('estudiante/' . $slug) : 'estudiante';
    echo "\nListing files under: $baseDir\n";
    $list = $disk->allFiles($baseDir);
    echo "Found " . count($list) . " files\n";
    foreach (array_slice($list,0,50) as $p) {
        echo " - $p\n";
    }
} catch (Exception $e) {
    echo "Error listing files: " . $e->getMessage() . "\n";
}

exit(0);
