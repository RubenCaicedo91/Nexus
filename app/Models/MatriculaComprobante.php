<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MatriculaComprobante extends Model
{
    protected $table = 'matricula_comprobantes';

    protected $fillable = [
        'matricula_id',
        'filename',
        'path',
        'original_name',
        'uploaded_by',
    ];

    public function matricula()
    {
        return $this->belongsTo(Matricula::class, 'matricula_id');
    }
}
