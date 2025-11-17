<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Actividad extends Model
{
    protected $table = 'actividades';

    protected $fillable = [
        'nota_id',
        'nombre',
        'valor'
    ];

    protected $casts = [
        'valor' => 'float'
    ];

    public function nota()
    {
        return $this->belongsTo(Nota::class, 'nota_id');
    }
}
