<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Pension extends Model
{
    protected $table = 'pensiones';
    
    protected $fillable = [
        'estudiante_id',
        'acudiente_id',
        'curso_id',
        'grado',
        'concepto',
        // Normalizado: usar las columnas canonicas de la tabla
        'mes_correspondiente',
        'año_correspondiente',
        'valor_base',
        'descuentos',
        'recargos',
        'valor_total',
        'fecha_vencimiento',
        'fecha_pago',
        'estado',
        'metodo_pago',
        'recargo_mora',
        'fecha_recargo',
        'observaciones',
        'comprobante_pago',
        'numero_recibo',
        'procesado_por'
    ];

    protected $dates = [
        'fecha_vencimiento',
        'fecha_pago',
        'fecha_recargo'
    ];

    // Estados de pensión
    const ESTADO_PENDIENTE = 'pendiente';
    const ESTADO_PAGADA = 'pagada';
    const ESTADO_VENCIDA = 'vencida';
    const ESTADO_ANULADA = 'anulada';

    // Métodos de pago
    const METODO_EFECTIVO = 'efectivo';
    const METODO_TRANSFERENCIA = 'transferencia';
    const METODO_TARJETA = 'tarjeta';
    const METODO_CONSIGNACION = 'consignacion';
    const METODO_PSE = 'pse';

    // Relaciones
    public function estudiante()
    {
        return $this->belongsTo(User::class, 'estudiante_id');
    }

    public function acudiente()
    {
        return $this->belongsTo(User::class, 'acudiente_id');
    }

    public function curso()
    {
        return $this->belongsTo(Curso::class);
    }

    public function procesadoPor()
    {
        return $this->belongsTo(User::class, 'procesado_por');
    }

    // Mutators
    public function setValorBaseAttribute($value)
    {
        $this->attributes['valor_base'] = $value * 100; // Guardar en centavos
    }

    public function setDescuentosAttribute($value)
    {
        $this->attributes['descuentos'] = $value * 100;
    }

    public function setRecargosAttribute($value)
    {
        $this->attributes['recargos'] = $value * 100;
    }

    public function setValorTotalAttribute($value)
    {
        $this->attributes['valor_total'] = $value * 100;
    }

    public function setRecargoMoraAttribute($value)
    {
        $this->attributes['recargo_mora'] = $value * 100;
    }

    // Accessors
    public function getValorBaseAttribute($value)
    {
        return $value / 100; // Convertir de centavos a pesos
    }

    public function getDescuentosAttribute($value)
    {
        return $value / 100;
    }

    public function getRecargosAttribute($value)
    {
        return $value / 100;
    }

    public function getValorTotalAttribute($value)
    {
        return $value / 100;
    }

    public function getRecargoMoraAttribute($value)
    {
        return $value / 100;
    }

    // Métodos de utilidad
    public function isPendiente()
    {
        return $this->estado === self::ESTADO_PENDIENTE;
    }

    public function isPagada()
    {
        return $this->estado === self::ESTADO_PAGADA;
    }

    public function isVencida()
    {
        return $this->estado === self::ESTADO_VENCIDA;
    }

    public function isAnulada()
    {
        return $this->estado === self::ESTADO_ANULADA;
    }

    public function diasVencida()
    {
        if (!$this->isVencida()) {
            return 0;
        }

        return Carbon::now()->diffInDays(Carbon::parse($this->fecha_vencimiento));
    }

    public function calcularRecargo()
    {
        if (!$this->isVencida() || $this->isPagada()) {
            return 0;
        }

        $diasVencida = $this->diasVencida();
        $porcentajeRecargo = 0;

        // Calcular recargo por mora
        if ($diasVencida <= 30) {
            $porcentajeRecargo = 0.02; // 2% primer mes
        } elseif ($diasVencida <= 60) {
            $porcentajeRecargo = 0.05; // 5% segundo mes
        } else {
            $porcentajeRecargo = 0.10; // 10% después del segundo mes
        }

        return ($this->valor_base + $this->recargos - $this->descuentos) * $porcentajeRecargo;
    }

    public function actualizarRecargo()
    {
        if ($this->isVencida() && !$this->isPagada()) {
            $nuevoRecargo = $this->calcularRecargo();
            
            if ($nuevoRecargo != $this->recargo_mora) {
                $this->recargo_mora = $nuevoRecargo;
                $this->fecha_recargo = Carbon::now();
                $this->valor_total = $this->valor_base + $this->recargos - $this->descuentos + $this->recargo_mora;
                $this->save();
            }
        }
    }

    public function marcarComoPagada($metodoPago, $numeroRecibo, $procesadoPor)
    {
        $this->estado = self::ESTADO_PAGADA;
        $this->fecha_pago = Carbon::now();
        $this->metodo_pago = $metodoPago;
        $this->numero_recibo = $numeroRecibo;
        $this->procesado_por = $procesadoPor;
        $this->save();
    }

    public function anular($observaciones = null)
    {
        $this->estado = self::ESTADO_ANULADA;
        if ($observaciones) {
            $this->observaciones = $observaciones;
        }
        $this->save();
    }

    // Scopes
    public function scopePendientes($query)
    {
        return $query->where('estado', self::ESTADO_PENDIENTE);
    }

    public function scopePagadas($query)
    {
        return $query->where('estado', self::ESTADO_PAGADA);
    }

    public function scopeVencidas($query)
    {
        return $query->where('estado', self::ESTADO_VENCIDA);
    }

    public function scopeDelMes($query, $mes, $año = null)
    {
        $año = $año ?: date('Y');
        return $query->where('mes_correspondiente', $mes)->where('año_correspondiente', $año);
    }

    public function scopeDelEstudiante($query, $estudianteId)
    {
        return $query->where('estudiante_id', $estudianteId);
    }

    public function scopeDelAcudiente($query, $acudienteId)
    {
        return $query->where('acudiente_id', $acudienteId);
    }

    // Boot method para eventos del modelo
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($pension) {
            // Calcular valor total al crear
            $pension->valor_total = $pension->valor_base + $pension->recargos - $pension->descuentos;
            
            // Verificar si está vencida al momento de crear
            if (Carbon::parse($pension->fecha_vencimiento)->isPast() && $pension->estado === self::ESTADO_PENDIENTE) {
                $pension->estado = self::ESTADO_VENCIDA;
            }
        });

        static::updating(function ($pension) {
            // Recalcular valor total si cambian los valores base
            if ($pension->isDirty(['valor_base', 'recargos', 'descuentos', 'recargo_mora'])) {
                $pension->valor_total = $pension->valor_base + $pension->recargos - $pension->descuentos + $pension->recargo_mora;
            }
        });
    }

    /**
     * Compatibilidad de atributos: exponer `mes` y `año` como alias de
     * `mes_correspondiente` y `año_correspondiente` para mantener la API
     * existente en vistas y controladores.
     */
    public function getMesAttribute()
    {
        return $this->attributes['mes_correspondiente'] ?? null;
    }

    public function setMesAttribute($value)
    {
        $this->attributes['mes_correspondiente'] = $value;
    }

    public function getAñoAttribute()
    {
        return $this->attributes['año_correspondiente'] ?? null;
    }

    public function setAñoAttribute($value)
    {
        $this->attributes['año_correspondiente'] = $value;
    }
}
