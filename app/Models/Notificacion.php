<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notificacion extends Model
{
    use HasFactory;

    protected $table = 'notificaciones'; // 👈 fuerza el nombre correcto

    protected $fillable = [
        'usuario_id',
        'titulo',
        'mensaje',
        'leida',
        'fecha',
        'creador_id',
        'solo_acudiente_responde',
        'solo_lectura',
        'tipo',
        'group_key',
        'deleted_by_creador',
    ];

    protected $casts = [
        'leida' => 'boolean',
        'solo_acudiente_responde' => 'boolean',
        'solo_lectura' => 'boolean',
        'fecha' => 'datetime',
    ];

    /**
     * Usuario que creó la notificación (remitente)
     */
    public function creador()
    {
        return $this->belongsTo(User::class, 'creador_id');
    }

    /**
     * Usuario destinatario de la notificación
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
