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
        'documento_identidad',
        'rh',
        'certificado_medico',
        'certificado_notas',
        'comprobante_pago',
        'monto_pago',
        'fecha_pago',
        'documentos_completos',
    ];

    // Agregar atributos virtuales para obtener URL de descarga/visualización
    protected $appends = [
        'documento_identidad_url',
        'rh_url',
        'certificado_medico_url',
        'certificado_notas_url',
        'comprobante_pago_url',
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

    public function getComprobantePagoUrlAttribute()
    {
        if (empty($this->comprobante_pago)) return null;
        return route('matriculas.archivo', ['matricula' => $this->id, 'campo' => 'comprobante_pago']);
    }

    // Relación con curso
    public function curso()
    {
        return $this->belongsTo(Curso::class);
    }

    /**
     * Verificar si todos los documentos están completos
     */
    public function tieneDocumentosCompletos()
    {
        return !empty($this->documento_identidad) &&
               !empty($this->rh) &&
               !empty($this->certificado_medico) &&
               !empty($this->certificado_notas) &&
               !empty($this->comprobante_pago) &&
               !empty($this->monto_pago) &&
               !empty($this->fecha_pago);
    }

    /**
     * Actualizar estado de documentos completos
     */
    public function actualizarEstadoDocumentos()
    {
        $this->documentos_completos = $this->tieneDocumentosCompletos();
        $this->save();
        return $this->documentos_completos;
    }
}
