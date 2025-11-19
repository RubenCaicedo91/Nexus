<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asistencia extends Model
{
    use HasFactory;

    protected $fillable = [
        'fecha',
        'curso_id',
        'matricula_id',
        'materia_id',
        'estudiante_id',
        'presente',
        'observacion',
    ];

    protected $casts = [
        'fecha' => 'date',
        'presente' => 'boolean',
    ];

    public function curso()
    {
        return $this->belongsTo(Curso::class);
    }

    public function matricula()
    {
        return $this->belongsTo(Matricula::class);
    }

    public function materia()
    {
        return $this->belongsTo(Materia::class);
    }

    public function estudiante()
    {
        return $this->belongsTo(User::class, 'estudiante_id');
    }
}
