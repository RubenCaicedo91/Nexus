<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReporteDisciplinario extends Model
{
    protected $table = 'reportes_disciplinarios';

    protected $fillable = [
        'user_id','reporter_id','curso_id','fecha_incidencia',
        'descripcion','gravedad','estado','sancion_id','evidencia'
    ];

    protected $casts = [
        'evidencia' => 'array',
        'fecha_incidencia' => 'datetime',
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function reporter() { return $this->belongsTo(User::class, 'reporter_id'); }
    public function curso() { return $this->belongsTo(Curso::class, 'curso_id'); }
    public function sancion() { return $this->belongsTo(Sancion::class); }
}
