<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notificacion extends Model
{
    use HasFactory;

    protected $table = 'notificaciones'; // 👈 fuerza el nombre correcto

    protected $fillable = [
        'usuario_id',
        'titulo',
        'mensaje',
        'leida',
        'fecha',
    ];
}
