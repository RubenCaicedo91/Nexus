<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SancionTipo extends Model
{
    use HasFactory;

    protected $table = 'sancion_tipos';

    protected $fillable = [
        'nombre', 'descripcion', 'categoria', 'severidad', 'duracion_defecto_dias', 'activo'
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];
}
