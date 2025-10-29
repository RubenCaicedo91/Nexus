<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Matricula;
use Illuminate\Support\Facades\Storage;

$mat = Matricula::first();
if (!$mat) { echo "No hay matriculas\n"; exit(0); }

echo "Matricula id={$mat->id}\n";
$campos = ['documento_identidad','rh','certificado_medico','certificado_notas'];
foreach ($campos as $c) {
    $v = $mat->$c;
    echo "$c: ";
    var_export($v);
    echo "\n";
    if ($v) {
        $disk = Storage::disk('ftp_matriculas');
        $exists = $disk->exists($v) ? 'YES' : 'NO';
        echo "  exists? $exists\n";
        try {
            $url = $disk->url($v);
            echo "  url: $url\n";
        } catch (Exception $e) {
            echo "  url: (error) " . $e->getMessage() . "\n";
        }
    }
}
