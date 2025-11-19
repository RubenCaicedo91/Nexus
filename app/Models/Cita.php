<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Cita extends Model
{
    use HasFactory;

    protected $table = 'citas';

    protected $fillable = [
        'parent_cita_id',
        'solicitante_id',
        'orientador_id',
        'estudiante_referido_id',
        'tipo_cita',
        'modalidad',
        'motivo',
        'descripcion',
        'observaciones_previas',
        'fecha_solicitada',
        'hora_solicitada',
        'fecha_asignada',
        'hora_asignada',
        'duracion_estimada',
        'estado',
        'prioridad',
        'resumen_cita',
        'recomendaciones',
        'plan_seguimiento',
        'requiere_seguimiento',
        'fecha_seguimiento',
        'lugar_cita',
        'link_virtual',
        'instrucciones_adicionales',
        'motivo_cancelacion',
        'fecha_cancelacion',
        'cancelado_por'
    ];

    protected $casts = [
        'fecha_solicitada' => 'date',
        'fecha_asignada' => 'date',
        'fecha_seguimiento' => 'date',
        'hora_seguimiento' => 'string',
        'fecha_cancelacion' => 'datetime',
        'requiere_seguimiento' => 'boolean',
        'duracion_estimada' => 'integer',
        'hora_solicitada' => 'string',
        'hora_asignada' => 'string'
    ];

    // Estados posibles de la cita
    const ESTADOS = [
        'solicitada' => 'Solicitada',
        'programada' => 'Programada',
        'confirmada' => 'Confirmada',
        'en_curso' => 'En Curso',
        'completada' => 'Completada',
        'cancelada' => 'Cancelada',
        'reprogramada' => 'Reprogramada'
    ];

    // Prioridades posibles
    const PRIORIDADES = [
        'baja' => 'Baja',
        'media' => 'Media',
        'alta' => 'Alta',
        'urgente' => 'Urgente'
    ];

    // Tipos de cita
    const TIPOS_CITA = [
        'orientacion' => 'Orientación Académica',
        'psicologica' => 'Orientación Psicológica',
        'vocacional' => 'Orientación Vocacional',
        'otro' => 'Otro'
    ];

    // Modalidades
    const MODALIDADES = [
        'presencial' => 'Presencial',
        'virtual' => 'Virtual',
        'telefonica' => 'Telefónica'
    ];

    /**
     * Relación con el usuario solicitante
     */
    public function solicitante()
    {
        return $this->belongsTo(User::class, 'solicitante_id');
    }

    /**
     * Cita origen (si esta cita es un seguimiento)
     */
    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_cita_id');
    }

    /**
     * Seguimientos derivados de esta cita
     */
    public function children()
    {
        return $this->hasMany(self::class, 'parent_cita_id');
    }

    /**
     * Relación con el orientador asignado
     */
    public function orientador()
    {
        return $this->belongsTo(User::class, 'orientador_id');
    }

    /**
     * Relación con el estudiante referido
     */
    public function estudianteReferido()
    {
        return $this->belongsTo(User::class, 'estudiante_referido_id');
    }

    /**
     * Relación con quien canceló la cita
     */
    public function canceladoPor()
    {
        return $this->belongsTo(User::class, 'cancelado_por');
    }

    /**
     * Relación con informes (manteniendo compatibilidad)
     */
    public function informe()
    {
        return $this->hasOne(Informe::class);
    }

    /**
     * Scopes para filtrar citas
     */
    public function scopePorEstado($query, $estado)
    {
        return $query->where('estado', $estado);
    }

    public function scopePendientes($query)
    {
        return $query->whereIn('estado', ['solicitada', 'programada', 'confirmada']);
    }

    public function scopeCompletadas($query)
    {
        return $query->where('estado', 'completada');
    }

    public function scopePorFecha($query, $fecha)
    {
        return $query->where('fecha_asignada', $fecha);
    }

    public function scopePorOrientador($query, $orientadorId)
    {
        return $query->where('orientador_id', $orientadorId);
    }

    public function scopePorSolicitante($query, $solicitanteId)
    {
        return $query->where('solicitante_id', $solicitanteId);
    }

    /**
     * Accessors y Mutators
     */
    public function getEstadoFormateadoAttribute()
    {
        return self::ESTADOS[$this->estado] ?? $this->estado;
    }

    public function getPrioridadFormateadaAttribute()
    {
        return self::PRIORIDADES[$this->prioridad] ?? $this->prioridad;
    }

    public function getTipoCitaFormateadoAttribute()
    {
        return self::TIPOS_CITA[$this->tipo_cita] ?? $this->tipo_cita;
    }

    public function getModalidadFormateadaAttribute()
    {
        return self::MODALIDADES[$this->modalidad] ?? $this->modalidad;
    }

    public function getFechaHoraCompletaAttribute()
    {
        if ($this->fecha_asignada && $this->hora_asignada) {
            return Carbon::parse($this->fecha_asignada . ' ' . $this->hora_asignada);
        }
        return null;
    }

    public function getDuracionFormateadaAttribute()
    {
        $horas = intval($this->duracion_estimada / 60);
        $minutos = $this->duracion_estimada % 60;
        
        if ($horas > 0) {
            return $horas . 'h ' . $minutos . 'min';
        }
        return $minutos . ' minutos';
    }

    /**
     * Métodos de ayuda
     */
    public function esVirtual()
    {
        return $this->modalidad === 'virtual';
    }

    public function esPendiente()
    {
        return in_array($this->estado, ['solicitada', 'programada', 'confirmada']);
    }

    public function esCompletada()
    {
        return $this->estado === 'completada';
    }

    public function esCancelada()
    {
        return $this->estado === 'cancelada';
    }

    public function puedeSerCancelada()
    {
        return in_array($this->estado, ['solicitada', 'programada', 'confirmada']);
    }

    public function puedeSerReprogramada()
    {
        return in_array($this->estado, ['solicitada', 'programada', 'confirmada']);
    }

    /**
     * Métodos para cambiar estado
     */
    public function programar($fechaAsignada, $horaAsignada, $orientadorId = null)
    {
        $this->update([
            'estado' => 'programada',
            'fecha_asignada' => $fechaAsignada,
            'hora_asignada' => $horaAsignada,
            'orientador_id' => $orientadorId ?? $this->orientador_id
        ]);
    }

    public function confirmar()
    {
        $this->update(['estado' => 'confirmada']);
    }

    public function iniciar()
    {
        $this->update(['estado' => 'en_curso']);
    }

    public function completar($resumen = null, $recomendaciones = null, $planSeguimiento = null)
    {
        $data = ['estado' => 'completada'];
        
        if ($resumen) $data['resumen_cita'] = $resumen;
        if ($recomendaciones) $data['recomendaciones'] = $recomendaciones;
        if ($planSeguimiento) $data['plan_seguimiento'] = $planSeguimiento;
        
        $this->update($data);
    }

    public function cancelar($motivo, $canceladoPor)
    {
        $this->update([
            'estado' => 'cancelada',
            'motivo_cancelacion' => $motivo,
            'fecha_cancelacion' => now(),
            'cancelado_por' => $canceladoPor
        ]);
    }

    public function reprogramar($nuevaFecha, $nuevaHora)
    {
        $this->update([
            'estado' => 'reprogramada',
            'fecha_asignada' => $nuevaFecha,
            'hora_asignada' => $nuevaHora
        ]);
    }
}
