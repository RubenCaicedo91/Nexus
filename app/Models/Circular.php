<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Circular extends Model
{
    use HasFactory;

    protected $fillable = [
        'titulo',
        'contenido',
        'fecha_publicacion',
        'archivo',
        'creador_id',
    ];

    public function creador()
    {
        return $this->belongsTo(\App\Models\User::class, 'creador_id');
    }
}
