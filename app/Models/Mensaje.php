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
        'parent_id',
    ];

    // Relaciones
    public function remitente()
    {
        return $this->belongsTo(\App\Models\User::class, 'remitente_id');
    }

    public function destinatario()
    {
        return $this->belongsTo(\App\Models\User::class, 'destinatario_id');
    }

    public function parent()
    {
        return $this->belongsTo(\App\Models\Mensaje::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(\App\Models\Mensaje::class, 'parent_id');
    }
}
