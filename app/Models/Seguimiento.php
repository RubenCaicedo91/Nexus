<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Seguimiento extends Model
{
    use HasFactory;

    protected $fillable = [
        'estudiante_id',
        'responsable_id',
        'cita_id',
        'tipo_seguimiento',
        'area_enfoque',
        'titulo',
        'descripcion_situacion',
        'observaciones',
        'observaciones_comportamiento',
        'nivel_gravedad',
        'estado_seguimiento',
        'acciones_realizadas',
        'recomendaciones',
        'plan_accion',
        'recursos_utilizados',
        'fecha',
        'fecha_identificacion',
        'fecha_primera_intervencion',
        'fecha_ultima_revision',
        'fecha_proxima_revision',
        'numero_sesiones',
        'participantes_involucrados',
        'padres_informados',
        'fecha_comunicacion_padres',
        'respuesta_padres',
        'logros_alcanzados',
        'dificultades_encontradas',
        'nivel_mejora',
        'evaluacion_final',
        'requiere_atencion_especializada',
        'derivado_a',
        'notas_adicionales',
        'confidencial'
    ];

    protected $casts = [
        'fecha' => 'date',
        'fecha_identificacion' => 'date',
        'fecha_primera_intervencion' => 'date',
        'fecha_ultima_revision' => 'date',
        'fecha_proxima_revision' => 'date',
        'fecha_comunicacion_padres' => 'date',
        'padres_informados' => 'boolean',
        'requiere_atencion_especializada' => 'boolean',
        'confidencial' => 'boolean',
        'numero_sesiones' => 'integer',
        'participantes_involucrados' => 'array'
    ];

    // Constantes para tipos y estados
    const TIPOS_SEGUIMIENTO = [
        'academico' => 'Académico',
        'disciplinario' => 'Disciplinario',
        'psicologico' => 'Psicológico',
        'familiar' => 'Familiar',
        'vocacional' => 'Vocacional',
        'convivencia' => 'Convivencia',
        'adaptacion' => 'Adaptación'
    ];

    const AREAS_ENFOQUE = [
        'rendimiento_academico' => 'Rendimiento Académico',
        'comportamiento' => 'Comportamiento',
        'asistencia' => 'Asistencia',
        'participacion' => 'Participación',
        'relaciones_interpersonales' => 'Relaciones Interpersonales',
        'desarrollo_emocional' => 'Desarrollo Emocional',
        'orientacion_vocacional' => 'Orientación Vocacional',
        'situacion_familiar' => 'Situación Familiar',
        'adaptacion_escolar' => 'Adaptación Escolar'
    ];

    const NIVELES_GRAVEDAD = [
        'bajo' => 'Bajo',
        'medio' => 'Medio',
        'alto' => 'Alto',
        'critico' => 'Crítico'
    ];

    const ESTADOS_SEGUIMIENTO = [
        'activo' => 'Activo',
        'en_proceso' => 'En Proceso',
        'pausado' => 'Pausado',
        'completado' => 'Completado',
        'derivado' => 'Derivado'
    ];

    const NIVELES_MEJORA = [
        'ninguna' => 'Ninguna',
        'leve' => 'Leve',
        'moderada' => 'Moderada',
        'significativa' => 'Significativa'
    ];

    /**
     * Relaciones
     */
    public function estudiante()
    {
        return $this->belongsTo(User::class, 'estudiante_id');
    }

    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function cita()
    {
        return $this->belongsTo(Cita::class, 'cita_id');
    }

    /**
     * Scopes
     */
    public function scopePorEstudiante($query, $estudianteId)
    {
        return $query->where('estudiante_id', $estudianteId);
    }

    public function scopePorResponsable($query, $responsableId)
    {
        return $query->where('responsable_id', $responsableId);
    }

    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo_seguimiento', $tipo);
    }

    public function scopePorEstado($query, $estado)
    {
        return $query->where('estado_seguimiento', $estado);
    }

    public function scopeActivos($query)
    {
        return $query->whereIn('estado_seguimiento', ['activo', 'en_proceso']);
    }

    public function scopeRequierenRevision($query)
    {
        return $query->where('fecha_proxima_revision', '<=', now())
                    ->whereIn('estado_seguimiento', ['activo', 'en_proceso']);
    }

    public function scopeGravedadAlta($query)
    {
        return $query->whereIn('nivel_gravedad', ['alto', 'critico']);
    }

    public function scopeConfidenciales($query)
    {
        return $query->where('confidencial', true);
    }

    /**
     * Accessors
     */
    public function getTipoSeguimientoFormateadoAttribute()
    {
        return self::TIPOS_SEGUIMIENTO[$this->tipo_seguimiento] ?? $this->tipo_seguimiento;
    }

    public function getAreaEnfoqueFormateadaAttribute()
    {
        return self::AREAS_ENFOQUE[$this->area_enfoque] ?? $this->area_enfoque;
    }

    public function getNivelGravedadFormateadoAttribute()
    {
        return self::NIVELES_GRAVEDAD[$this->nivel_gravedad] ?? $this->nivel_gravedad;
    }

    public function getEstadoSeguimientoFormateadoAttribute()
    {
        return self::ESTADOS_SEGUIMIENTO[$this->estado_seguimiento] ?? $this->estado_seguimiento;
    }

    public function getNivelMejoraFormateadoAttribute()
    {
        return self::NIVELES_MEJORA[$this->nivel_mejora] ?? $this->nivel_mejora;
    }

    public function getDiasDesdeIdentificacionAttribute()
    {
        $fechaBase = $this->fecha_identificacion ?? $this->fecha;
        return $fechaBase ? now()->diffInDays($fechaBase) : 0;
    }

    public function getDiasParaRevisionAttribute()
    {
        return $this->fecha_proxima_revision ? now()->diffInDays($this->fecha_proxima_revision, false) : null;
    }

    /**
     * Métodos de ayuda
     */
    public function esActivo()
    {
        return in_array($this->estado_seguimiento, ['activo', 'en_proceso']);
    }

    public function esGravedad($nivel)
    {
        return $this->nivel_gravedad === $nivel;
    }

    public function esGravedadAlta()
    {
        return in_array($this->nivel_gravedad, ['alto', 'critico']);
    }

    public function requiereRevision()
    {
        return $this->fecha_proxima_revision && 
               $this->fecha_proxima_revision <= now() && 
               $this->esActivo();
    }

    public function tieneRevisionPendiente()
    {
        return $this->requiereRevision();
    }

    public function puedeSerEditado()
    {
        return in_array($this->estado_seguimiento, ['activo', 'en_proceso', 'pausado']);
    }

    /**
     * Métodos para cambiar estado
     */
    public function marcarEnProceso()
    {
        $this->update([
            'estado_seguimiento' => 'en_proceso',
            'fecha_primera_intervencion' => $this->fecha_primera_intervencion ?: now()
        ]);
    }

    public function pausar($motivo = null)
    {
        $data = ['estado_seguimiento' => 'pausado'];
        if ($motivo) {
            $data['notas_adicionales'] = ($this->notas_adicionales ? $this->notas_adicionales . "\n\n" : '') . 
                                        "PAUSADO: " . $motivo . " - " . now()->format('d/m/Y H:i');
        }
        $this->update($data);
    }

    public function completar($evaluacionFinal = null, $logrosAlcanzados = null)
    {
        $data = [
            'estado_seguimiento' => 'completado',
            'fecha_ultima_revision' => now()
        ];
        
        if ($evaluacionFinal) $data['evaluacion_final'] = $evaluacionFinal;
        if ($logrosAlcanzados) $data['logros_alcanzados'] = $logrosAlcanzados;
        
        $this->update($data);
    }

    public function derivar($institucion, $motivo = null)
    {
        $data = [
            'estado_seguimiento' => 'derivado',
            'derivado_a' => $institucion,
            'requiere_atencion_especializada' => true
        ];
        
        if ($motivo) {
            $data['notas_adicionales'] = ($this->notas_adicionales ? $this->notas_adicionales . "\n\n" : '') . 
                                        "DERIVADO: " . $motivo . " - " . now()->format('d/m/Y H:i');
        }
        
        $this->update($data);
    }

    public function programarRevision($fecha, $notas = null)
    {
        $data = ['fecha_proxima_revision' => $fecha];
        
        if ($notas) {
            $data['notas_adicionales'] = ($this->notas_adicionales ? $this->notas_adicionales . "\n\n" : '') . 
                                        "REVISIÓN PROGRAMADA: " . $notas . " - " . now()->format('d/m/Y H:i');
        }
        
        $this->update($data);
    }

    public function registrarSesion($observaciones = null, $acciones = null)
    {
        $data = [
            'numero_sesiones' => $this->numero_sesiones + 1,
            'fecha_ultima_revision' => now()
        ];
        
        if ($observaciones) {
            $data['observaciones'] = ($this->observaciones ? $this->observaciones . "\n\n" : '') . 
                                   "SESIÓN " . ($this->numero_sesiones + 1) . " (" . now()->format('d/m/Y') . "): " . $observaciones;
        }
        
        if ($acciones) {
            $data['acciones_realizadas'] = ($this->acciones_realizadas ? $this->acciones_realizadas . "\n\n" : '') . 
                                          "SESIÓN " . ($this->numero_sesiones + 1) . ": " . $acciones;
        }
        
        $this->update($data);
    }

    public function informarPadres($fechaComunicacion = null, $respuesta = null)
    {
        $data = [
            'padres_informados' => true,
            'fecha_comunicacion_padres' => $fechaComunicacion ?: now()
        ];
        
        if ($respuesta) {
            $data['respuesta_padres'] = $respuesta;
        }
        
        $this->update($data);
    }

    /**
     * Métodos de búsqueda y filtrado
     */
    public static function buscarPorTexto($texto)
    {
        return static::where(function($query) use ($texto) {
            $query->where('titulo', 'LIKE', "%{$texto}%")
                  ->orWhere('descripcion_situacion', 'LIKE', "%{$texto}%")
                  ->orWhere('observaciones', 'LIKE', "%{$texto}%")
                  ->orWhereHas('estudiante', function($q) use ($texto) {
                      $q->where('name', 'LIKE', "%{$texto}%");
                  });
        });
    }

    public static function reporteEstadisticas()
    {
        return [
            'total' => static::count(),
            'activos' => static::activos()->count(),
            'gravedad_alta' => static::gravedadAlta()->count(),
            'requieren_revision' => static::requierenRevision()->count(),
            'por_tipo' => static::selectRaw('tipo_seguimiento, COUNT(*) as total')
                               ->groupBy('tipo_seguimiento')
                               ->pluck('total', 'tipo_seguimiento'),
            'por_estado' => static::selectRaw('estado_seguimiento, COUNT(*) as total')
                                 ->groupBy('estado_seguimiento')
                                 ->pluck('total', 'estado_seguimiento')
        ];
    }
}
