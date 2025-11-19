<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sancion extends Model
{
    protected $fillable = ['usuario_id', 'descripcion', 'tipo', 'tipo_id', 'fecha', 'fecha_inicio', 'fecha_fin', 'monto', 'pago_obligatorio', 'pago_observacion', 'reunion_at'];

    protected $casts = [
        'fecha' => 'date',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'monto' => 'decimal:2',
        'pago_obligatorio' => 'boolean',
        'reunion_at' => 'datetime',
    ];

    /**
     * Relación: sanción -> reportes disciplinarios asociados.
     * Usamos el nombre de clase como string para evitar errores de "class not found"
     * en el editor/IDE si el modelo aún no existe.
     */
    public function reportes()
    {
        return $this->hasMany('App\\Models\\ReporteDisciplinario');
    }

    /**
     * Relación al usuario (estudiante) al que se le aplicó la sanción.
     */
    public function usuario()
    {
        return $this->belongsTo(\App\Models\User::class, 'usuario_id');
    }

    /**
     * Relación al tipo normalizado (sancion_tipos)
     */
    public function tipo_rel()
    {
        return $this->belongsTo(\App\Models\SancionTipo::class, 'tipo_id');
    }
}
