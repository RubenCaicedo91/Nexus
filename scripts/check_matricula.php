<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$id = $argv[1] ?? 2;
$m = App\Models\Matricula::find($id);
if ($m) {
    echo "FOUND: id={$m->id}, user_id={$m->user_id}, fecha_matricula={$m->fecha_matricula}\n";
    exit(0);
}

echo "NOT FOUND\n";
exit(0);
