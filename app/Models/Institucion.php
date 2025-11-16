<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Institucion extends Model
{
    use HasFactory;

    protected $table = 'institucion';

    protected $fillable = [
        'nombre',
        'nit',
        'direccion',
        'telefono',
        'correo',
        'rector',
        'mision',
        'vision',
        'valores',
        'valor_matricula',
    ];
}