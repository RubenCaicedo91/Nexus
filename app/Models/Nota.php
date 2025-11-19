<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Nota extends Model
{
    protected $table = 'notas';

    protected $fillable = [
        'matricula_id',
        'materia_id',
        'anio',
        'valor',
        'observaciones',
        'definitiva',
        'definitiva_por',
        'definitiva_en'
    ];

    protected $casts = [
        'valor' => 'float',
        'aprobada' => 'boolean',
        'definitiva' => 'boolean'
    ];

    protected $dates = [
        'aprobado_en',
        'definitiva_en'
    ];

    // Approval relation
    public function aprobadoPor()
    {
        return $this->belongsTo(User::class, 'aprobado_por');
    }

    public function matricula()
    {
        return $this->belongsTo(Matricula::class);
    }

    public function materia()
    {
        return $this->belongsTo(Materia::class);
    }

    public function actividades()
    {
        return $this->hasMany(\App\Models\Actividad::class, 'nota_id');
    }

    /**
     * Recalcula el campo `valor` (0-100) y `aprobada` a partir
     * del promedio de `actividades` (que están en escala 0-5).
     * - Si hay actividades: valor = avg(actividades) * 20
     * - Si no hay actividades: mantiene `valor` y calcula `aprobada` desde él
     */
    public function recalculateFromActividades()
    {
        $this->load('actividades');
        $count = $this->actividades->count();

        if ($count > 0) {
            $avg = round($this->actividades->avg('valor'), 2); // 0-5
            $this->valor = ($avg / 5.0) * 100.0; // almacenar como 0-100
            $this->aprobada = ($avg >= 3.0);
        } else {
            // No hay actividades: conservar valor (si existe) y derivar aprobada
            $valor = $this->valor !== null ? floatval($this->valor) : 0.0;
            $calificacion = ($valor / 100.0) * 5.0; // convertir a 0-5
            $this->aprobada = ($calificacion >= 3.0);
        }

        $this->save();
        return $this;
    }
}
