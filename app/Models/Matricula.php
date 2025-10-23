<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Matricula extends Model
{
    protected $fillable = [
        'user_id',
        'curso_id',
        'fecha_matricula',
        'estado',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Assuming a Curso model will be created later
    // public function curso()
    // {
    //     return $this->belongsTo(Curso::class);
    // }
}
