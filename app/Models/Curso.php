<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Curso extends Model
{
    use HasFactory;

    protected $fillable = ['nombre', 'descripcion'];

    public function docentes()
    {
        return $this->belongsToMany(User::class, 'curso_docente', 'curso_id', 'docente_id')->withTimestamps();
    }

    public function materias()
    {
        return $this->hasMany(Materia::class, 'curso_id');
    }
}