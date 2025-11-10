<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class Mensaje extends Model
{
    use HasFactory;

    protected $fillable = [
        'remitente_id',
        'destinatario_id',
        'asunto',
        'contenido',
        'leido',
    ];
}
