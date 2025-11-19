<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$u = \App\Models\User::where('email', 'rector@colegio.edu.co')->with('role')->first();
if (! $u) {
    echo "USUARIO_NO_ENCONTRADO\n";
    exit(0);
}

echo "id: " . ($u->id ?? 'null') . "\n";
echo "roles_id: " . ($u->roles_id ?? 'null') . "\n";
echo "role nombre: " . (optional($u->role)->nombre ?? 'null') . "\n";
echo "role permisos: ";
if (optional($u->role)->permisos) {
    print_r($u->role->permisos);
} else {
    echo "(n/a)\n";
}

echo "has gestionar_academica: " . ($u->hasPermission('gestionar_academica') ? "SI" : "NO") . "\n";
echo "has gestionar_usuarios: " . ($u->hasPermission('gestionar_usuarios') ? "SI" : "NO") . "\n";

// Mostrar nombre exacto del rol en DB
$rol = \App\Models\RolesModel::find($u->roles_id);
echo "rolesModel nombre directo: " . ($rol->nombre ?? 'null') . "\n";

?>