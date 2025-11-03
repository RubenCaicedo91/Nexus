<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\RolesModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Matricula;

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

    /**
     * Relación: un usuario (estudiante) puede tener muchas matrículas
     * @return HasMany
     */
    public function matriculas(): HasMany
    {
        return $this->hasMany(Matricula::class, 'user_id');
    }

    /**
     * Cursos asignados cuando el usuario es docente (many-to-many)
     */
    public function cursosAsignados()
    {
        return $this->belongsToMany(Curso::class, 'curso_docente', 'docente_id', 'curso_id')->withTimestamps();
    }

    /**
     * Comprobar si el usuario tiene un permiso dado en su rol.
     * Maneja roles que son instancias de RolesModel o arrays/objetos (fallback file).
     * @param string $permiso
     * @return bool
     */
    public function hasPermission(string $permiso): bool
    {
        $role = $this->role;
        if (! $role) return false;

        // Si el rol tiene nombre con 'admin' o 'administrador', conceder todos los permisos (fallback)
        $roleNombre = null;
        if (is_object($role) && isset($role->nombre)) $roleNombre = $role->nombre;
        if (is_array($role) && isset($role['nombre'])) $roleNombre = $role['nombre'];
        if ($roleNombre && (stripos($roleNombre, 'admin') !== false || stripos($roleNombre, 'administrador') !== false)) {
            return true;
        }

        // Fallback legacy: si el usuario tiene roles_id == 1 conceder permiso
        if (isset($this->roles_id) && (int)$this->roles_id === 1) {
            return true;
        }

        // RolesModel tiene el método tienePermiso
        if (is_object($role) && method_exists($role, 'tienePermiso')) {
            return (bool) $role->tienePermiso($permiso);
        }

        // Si el role es un objeto o array con 'permisos'
        $permisos = null;
        if (is_object($role) && isset($role->permisos)) $permisos = $role->permisos;
        if (is_array($role) && isset($role['permisos'])) $permisos = $role['permisos'];

        if (is_string($permisos)) {
            $permisos = array_map('trim', explode(',', $permisos));
        }

        if (! is_array($permisos)) return false;
        return in_array($permiso, $permisos);
    }
}
