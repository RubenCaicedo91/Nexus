<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Matricula extends Model
{
    protected $fillable = [
        'user_id',
        'curso_id',
        'fecha_matricula',
        'estado',
    ];

    // Agregar atributos virtuales para obtener URL de descarga/visualización
    protected $appends = [
        'documento_identidad_url',
        'rh_url',
        'certificado_medico_url',
        'certificado_notas_url',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Accesores para generar rutas que servirán los archivos desde el controlador
    public function getDocumentoIdentidadUrlAttribute()
    {
        if (empty($this->documento_identidad)) return null;
        return route('matriculas.archivo', ['matricula' => $this->id, 'campo' => 'documento_identidad']);
    }

    public function getRhUrlAttribute()
    {
        if (empty($this->rh)) return null;
        return route('matriculas.archivo', ['matricula' => $this->id, 'campo' => 'rh']);
    }

    public function getCertificadoMedicoUrlAttribute()
    {
        if (empty($this->certificado_medico)) return null;
        return route('matriculas.archivo', ['matricula' => $this->id, 'campo' => 'certificado_medico']);
    }

    public function getCertificadoNotasUrlAttribute()
    {
        if (empty($this->certificado_notas)) return null;
        return route('matriculas.archivo', ['matricula' => $this->id, 'campo' => 'certificado_notas']);
    }

    // Assuming a Curso model will be created later
    // public function curso()
    // {
    //     return $this->belongsTo(Curso::class);
    // }
}
