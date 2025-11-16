<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class Matricula extends Model
{
    protected $fillable = [
        'user_id',
        'curso_id',
        'fecha_matricula',
        'tipo_usuario',
        'estado',
        'documento_identidad',
        'rh',
        'certificado_medico',
        'certificado_notas',
        'comprobante_pago',
        'monto_pago',
        'fecha_pago',
        'documentos_completos',
        'pago_validado',
        'pago_validado_por',
        'pago_validado_at',
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

    protected $casts = [
        'documentos_completos' => 'boolean',
        'pago_validado' => 'boolean',
        'pago_validado_at' => 'datetime',
    ];

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
        // Este método ahora solo verifica los documentos (sin incluir el pago)
        $complete = !empty($this->documento_identidad) &&
                    !empty($this->rh) &&
                    !empty($this->certificado_medico);

        // Si el tipo de usuario es 'antiguo', se requiere certificado_notas
        if (($this->tipo_usuario ?? null) === 'antiguo') {
            $complete = $complete && !empty($this->certificado_notas);
        }

        return $complete;
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

    /**
     * Determina si hay registro de pago en la matrícula.
     */
    public function hasPayment()
    {
        return !empty($this->comprobante_pago) || (!empty($this->monto_pago) && !empty($this->fecha_pago));
    }

    /**
     * Comprueba si los documentos (sin incluir pago) están completos.
     * Alias más explícito que `tieneDocumentosCompletos`.
     */
    public function documentsComplete()
    {
        return $this->tieneDocumentosCompletos();
    }

    /**
     * Recalcula y asigna el `estado` según las reglas:
     * - Si hay pago: `activo` (aunque falten documentos)
     * - Si no hay pago pero documentos completos: `completado`
     * - Si no hay pago y faltan documentos: `falta de documentacion`
     */
    public function recalcularEstado()
    {
        $docs = $this->documentsComplete();
        $paid = $this->hasPayment();

        // Actualizar flag de documentos
        $this->documentos_completos = (bool) $docs;

        if ($paid) {
            // Pago registrado: estado principal activo independientemente de documentos
            $this->estado = 'activo';
        } else {
            if ($docs) {
                $this->estado = 'completado';
            } else {
                $this->estado = 'falta de documentacion';
            }
        }
    }

    /**
     * Registrar hook para recalcular el estado antes de guardar.
     */
    protected static function booted()
    {
        static::saving(function ($matricula) {
            // Roles que pueden asignar manualmente un estado (no serán sobrescritos)
            $allowedRoles = ['Administrador_sistema', 'Administrador de sistema', 'Rector', 'Coordinador Académico', 'Coordinador Academico'];

            // Si el usuario autenticado pertenece a un rol autorizado y se proporciona
            // explícitamente un estado, respetarlo (no recalcular).
            try {
                $user = Auth::user();
            } catch (\Throwable $e) {
                $user = null;
            }

            if ($user && in_array(optional($user->role)->nombre, $allowedRoles)) {
                if (isset($matricula->estado) && $matricula->estado !== null) {
                    return;
                }
            }

            // Mantener el comportamiento previo: permitir 'suspendido' manual
            if (isset($matricula->estado) && $matricula->estado === 'suspendido') {
                return;
            }

            $matricula->recalcularEstado();
        });
    }
}
