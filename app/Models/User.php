<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\RolesModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'roles_id',
        'acudiente_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Relación al rol del usuario.
     * @return BelongsTo
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(RolesModel::class, 'roles_id');
    }

    /**
     * Si el usuario es un acudiente, esta relación devuelve los estudiantes que creó/asoció.
     * @return HasMany
     */
    public function acudientes(): HasMany
    {
        return $this->hasMany(self::class, 'acudiente_id');
    }

    /**
     * Para usuarios tipo estudiante: devuelve su acudiente (si existe)
     * @return BelongsTo
     */
    public function acudiente(): BelongsTo
    {
        return $this->belongsTo(self::class, 'acudiente_id');
    }
}
