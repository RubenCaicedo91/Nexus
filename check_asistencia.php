<?php
require __DIR__.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'autoload.php';
$app = require_once __DIR__.DIRECTORY_SEPARATOR.'bootstrap'.DIRECTORY_SEPARATOR.'app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Asistencia;

$a = Asistencia::where('fecha','2025-11-20')
    ->where('curso_id', 1)
    ->where('materia_id', 1)
    ->where('estudiante_id', 7)
    ->first();

if(!$a){
    echo "NULL\n"; exit;
}

$out = [
    'id' => $a->id,
    'fecha' => $a->fecha ? $a->fecha->format('Y-m-d') : null,
    'curso_id' => $a->curso_id,
    'materia_id' => $a->materia_id,
    'estudiante_id' => $a->estudiante_id,
    'presente_raw' => method_exists($a, 'getOriginal') ? $a->getOriginal('presente') : $a->presente,
    'presente_cast' => $a->presente ? 1 : 0,
    'observacion' => $a->observacion,
];

echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
