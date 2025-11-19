<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Mensaje;

echo "Total mensajes: " . Mensaje::count() . PHP_EOL;
foreach(Mensaje::with('remitente','destinatario')->orderBy('id','desc')->take(30)->get() as $m){
    printf("id:%d remitente:%s(%d) destinatario:%s(%d) leido:%s fecha:%s asunto:%s\n",
        $m->id,
        ($m->remitente ? $m->remitente->name : 'ID('.$m->remitente_id.')'),
        $m->remitente_id,
        ($m->destinatario ? $m->destinatario->name : 'ID('.$m->destinatario_id.')'),
        $m->destinatario_id,
        $m->leido ? 'si' : 'no',
        $m->created_at,
        str_replace("\n"," ",substr($m->asunto,0,60))
    );
}
