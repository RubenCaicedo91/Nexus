<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sancion extends Model
{
    protected $fillable = ['usuario_id', 'descripcion', 'tipo', 'fecha'];

    public function reportes() { return $this->hasMany(ReporteDisciplinario::class); }
}
