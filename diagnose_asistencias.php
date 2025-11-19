<?php
// script temporal para diagnosticar valores de asistencias
require __DIR__.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'autoload.php';
$app = require_once __DIR__.DIRECTORY_SEPARATOR.'bootstrap'.DIRECTORY_SEPARATOR.'app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
// Boot the application (without running a command)
$kernel->bootstrap();

use App\Models\Asistencia;

$rows = Asistencia::orderBy('fecha','desc')->take(60)->get()->map(function($a){
    return [
        'id' => $a->id,
        'fecha' => $a->fecha ? $a->fecha->format('Y-m-d') : null,
        'curso_id' => $a->curso_id,
        'materia_id' => $a->materia_id,
        'estudiante_id' => $a->estudiante_id,
        'presente_raw' => method_exists($a, 'getOriginal') ? $a->getOriginal('presente') : $a->presente,
        'presente_cast' => $a->presente ? 1 : 0,
        'observacion' => $a->observacion,
    ];
})->toArray();

echo json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
