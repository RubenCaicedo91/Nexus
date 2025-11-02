<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cita extends Model
{
    use HasFactory;

    protected $fillable = ['estudiante_id', 'fecha', 'estado'];

    public function informe()
    {
        return $this->hasOne(Informe::class);
    }
}
