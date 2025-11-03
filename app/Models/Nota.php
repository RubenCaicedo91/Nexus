<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Nota extends Model
{
    protected $table = 'notas';

    protected $fillable = [
        'matricula_id',
        'materia_id',
        'periodo',
        'valor',
        'observaciones'
    ];

    protected $casts = [
        'valor' => 'float',
        'aprobada' => 'boolean'
    ];

    // Approval relation
    public function aprobadoPor()
    {
        return $this->belongsTo(User::class, 'aprobado_por');
    }

    public function matricula()
    {
        return $this->belongsTo(Matricula::class);
    }

    public function materia()
    {
        return $this->belongsTo(Materia::class);
    }
}
