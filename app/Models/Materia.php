<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Materia extends Model
{
    use HasFactory;

    protected $table = 'materias';
    protected $fillable = [
        'nombre',
        'descripcion',
        'curso_id',
        'docente_id',
    ];

    public function curso()
    {
        return $this->belongsTo(Curso::class, 'curso_id');
    }

    /**
     * Relación many-to-many: una materia puede pertenecer a varios cursos.
     */
    public function cursos()
    {
        return $this->belongsToMany(Curso::class, 'curso_materia', 'materia_id', 'curso_id')->withTimestamps();
    }

    public function docente()
    {
        return $this->belongsTo(User::class, 'docente_id');
    }
}
