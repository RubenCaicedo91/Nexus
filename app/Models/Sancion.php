<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sancion extends Model
{
    protected $fillable = ['usuario_id', 'descripcion', 'tipo', 'fecha'];

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
}
