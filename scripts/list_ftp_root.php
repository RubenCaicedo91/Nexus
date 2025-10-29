<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Storage;

echo "Listing FTP disk 'ftp_matriculas' root files and directories:\n";
try {
    $disk = Storage::disk('ftp_matriculas');
    $dirs = $disk->directories();
    $files = $disk->allFiles();

    echo "Directories:\n";
    foreach ($dirs as $d) {
        echo " - $d\n";
    }

    echo "\nFiles (count: " . count($files) . "):\n";
    foreach ($files as $f) {
        echo " - $f\n";
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}

echo "Done.\n";
