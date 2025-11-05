<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsignarSancion extends Model
{
     protected $fillable = ['nombre','descripcion','duracion_dias','puntos'];

    public function reportes() { return $this->hasMany(ReporteDisciplinario::class); }
}
