<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$c = \App\Models\Circular::latest()->first();
if (! $c) {
    echo "No se encontró ninguna circular.\n";
    exit(0);
}
echo "ID: " . $c->id . PHP_EOL;
echo "archivo: " . ($c->archivo ?? 'null') . PHP_EOL;
echo "created_at: " . ($c->created_at ?? 'null') . PHP_EOL;
