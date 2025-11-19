<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Mensaje;

$u = User::orderBy('id')->take(2)->get();
if ($u->count() >= 2) {
    Mensaje::create([
        'remitente_id' => $u[0]->id,
        'destinatario_id' => $u[1]->id,
        'asunto' => 'Prueba automatizada',
        'contenido' => 'Mensaje creado por script de prueba',
        'leido' => false,
    ]);
    echo "Mensaje creado\n";
} else {
    echo "No hay suficientes usuarios\n";
}
