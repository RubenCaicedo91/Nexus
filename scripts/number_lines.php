<?php
$path = __DIR__ . '/../app/Http/Controllers/MatriculaController.php';
$lines = file($path);
foreach ($lines as $i => $line) {
    printf("%4d: %s", $i+1, rtrim($line));
    echo PHP_EOL;
}
