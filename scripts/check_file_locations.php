<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Matricula;
use Illuminate\Support\Facades\Storage;

$mat = Matricula::find(2);
if (!$mat) { echo "No matricula id=2\n"; exit; }
$campos = ['documento_identidad','rh','certificado_medico'];
foreach ($campos as $c) {
    $v = $mat->$c;
    echo "$c: "; var_export($v); echo "\n";
    if ($v) {
        // check ftp
        $ftp = Storage::disk('ftp_matriculas');
        echo "  ftp exists? " . ($ftp->exists($v) ? 'YES' : 'NO') . "\n";
        // check local private
        $local = storage_path('app/private/' . $v);
        echo "  local path: $local\n";
        echo "  local exists? " . (file_exists($local) ? 'YES' : 'NO') . "\n";
        // check public
        $public = storage_path('app/public/' . $v);
        echo "  public path: $public\n";
        echo "  public exists? " . (file_exists($public) ? 'YES' : 'NO') . "\n";
    }
}
