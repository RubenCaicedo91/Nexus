<?php

// Script para probar la carga de la página /gestion-disciplinaria index como usuario autenticado
// Ejecutar: php scripts/test_gestion_disciplinaria.php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

// Bootstrap del kernel de consola para inicializar facades, Eloquent, etc.
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\GestionDisciplinariaController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

try {
    $user = User::first();
    if (! $user) {
        echo "No hay usuarios en la base de datos. Crea uno y vuelve a intentar.\n";
        exit(1);
    }

    // Loguear en el guard por proceso (no crea sesión HTTP, pero permite que los controladores lean Auth::user())
    Auth::loginUsingId($user->id);

    $controller = new GestionDisciplinariaController();
    $response = $controller->index();

    if (method_exists($response, 'render')) {
        echo $response->render();
    } elseif (is_string($response)) {
        echo $response;
    } else {
        // Por si devuelve otra cosa (RedirectResponse, etc.)
        echo "Tipo de respuesta: " . get_class($response) . "\n";
        if (method_exists($response, 'getContent')) {
            echo $response->getContent();
        }
    }

} catch (\Throwable $e) {
    echo "Error al ejecutar prueba:\n";
    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString();
    exit(1);
}
