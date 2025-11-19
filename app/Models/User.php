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
        'first_name',
        'second_name',
        'first_last',
        'second_last',
        'email',
        'password',
        'roles_id',
        'acudiente_id',
        'document_type',
        'document_number',
        'celular',
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

    /**
     * Comprueba si el usuario tiene ALGUNO de los permisos del array.
     * Útil para decidir visibilidad de menús cuando basta con un permiso.
     * @param array $permisos
     * @return bool
     */
    public function hasAnyPermission(array $permisos): bool
    {
        foreach ($permisos as $p) {
            if ($this->hasPermission((string)$p)) return true;
        }
        return false;
    }

    /**
     * Mutator para normalizar el tipo de documento al guardarlo.
     * Guardaremos sin puntos y en mayúsculas (RC, CC, TI) para búsquedas y consistencia.
     * Acepta valores como "R.C", "RC", "r.c" y los normaliza a "RC".
     * @param mixed $value
     * @return void
     */
    public function setDocumentTypeAttribute($value): void
    {
        if (is_null($value) || $value === '') {
            $this->attributes['document_type'] = null;
            return;
        }

        // Eliminar puntos y espacios, pasar a mayúsculas
        $clean = strtoupper(str_replace(['.', ' '], '', trim($value)));

        // Sólo permitir las formas conocidas
        $allowed = ['RC', 'CC', 'TI'];
        if (in_array($clean, $allowed, true)) {
            $this->attributes['document_type'] = $clean;
        } else {
            // Si viene otro valor, lo guardamos tal cual (limpio y en mayúsculas)
            $this->attributes['document_type'] = $clean;
        }
    }
}
