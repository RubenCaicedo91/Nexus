<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\RolesModel;

/**
 * Controlador de roles que NO se conecta a la base de datos.
 * Usa un archivo JSON en storage/app/roles_fallback.json como almacenamiento.
 */
class RolController extends Controller
{
    private function storagePath()
    {
        return storage_path('app' . DIRECTORY_SEPARATOR . 'roles_fallback.json');
    }

    private function loadRoles(): array
    {
        $path = $this->storagePath();
        if (! file_exists($path)) {
            return [];
        }
        $json = file_get_contents($path);
        $data = json_decode($json, true);
        if (! is_array($data)) return [];
        return $data;
    }

    /**
     * Cargar roles desde la base de datos (Eloquent).
     * @return array
     */
    private function loadDbRoles(): array
    {
        try {
            $models = RolesModel::all();
            $out = [];
            foreach ($models as $m) {
                $out[] = [
                    'id' => $m->id,
                    'nombre' => $m->nombre,
                    'descripcion' => $m->descripcion,
                    'permisos' => $m->permisos ?? [],
                    'source' => 'db',
                ];
            }
            return $out;
        } catch (\Throwable $e) {
            // Si no hay conexión a BD o tabla, devolvemos vacío para fallback
            return [];
        }
    }

    private function saveRoles(array $roles): void
    {
        $path = $this->storagePath();
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }
        file_put_contents($path, json_encode(array_values($roles), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private function findRoleById(array $roles, $id)
    {
        foreach ($roles as $r) {
            if ((string)($r['id'] ?? '') === (string)$id) return $r;
        }
        return null;
    }

    private function nextId(array $roles)
    {
        $max = 0;
        foreach ($roles as $r) {
            $id = isset($r['id']) ? (int)$r['id'] : 0;
            if ($id > $max) $max = $id;
        }
        return $max + 1;
    }

    public function index(Request $request)
    {
        // Cargar roles desde DB y desde el archivo, y unirlos
        $dbRoles = $this->loadDbRoles();
        $fileRoles = $this->loadRoles();

        // Marcar origen de roles de archivo
        foreach ($fileRoles as &$fr) { $fr['source'] = $fr['source'] ?? 'file'; }
        unset($fr);

        // Preferir roles de DB en caso de id duplicado: indexamos por id
        $indexed = [];
        foreach (array_merge($dbRoles, $fileRoles) as $r) {
            $indexed[(string)($r['id'] ?? '')] = $r;
        }

        $roles = array_values($indexed);
        usort($roles, function ($a, $b) { return ($a['id'] ?? 0) <=> ($b['id'] ?? 0); });

        // Convertir arrays a objetos para compatibilidad con vistas que usan ->
        $rolesObj = array_map(fn($r) => (object)$r, $roles);

        if ($request->wantsJson()) {
            return response()->json(['data' => $rolesObj], 200);
        }

        return view('roles.index', ['roles' => $rolesObj]);
    }

    /**
     * Abortear con 403 si el usuario actual no es Administrador.
     */
    private function authorizeAdmin(): void
    {
        // Obtener usuario autenticado y asegurarnos que es una instancia del modelo User
        $user = Auth::user();
        // Intentar refrescar desde la BD para garantizar métodos del modelo (hasPermission)
        try {
            if (! $user || ! isset($user->id)) {
                abort(403, 'Acceso no autorizado');
            }
            $userModel = \App\Models\User::find($user->id);
        } catch (\Throwable $e) {
            abort(403, 'Acceso no autorizado');
        }

        // Permitir si el modelo tiene el permiso 'editar_roles', si el nombre del rol contiene 'admin'/'administrador'
        // o si el usuario tiene el role id 1 (fallback común para admin legacy)
        $canEdit = false;
        if ($userModel && method_exists($userModel, 'hasPermission') && $userModel->hasPermission('editar_roles')) {
            $canEdit = true;
        }
        $roleNombre = $userModel && isset($userModel->role) ? optional($userModel->role)->nombre : null;
        $roleId = $userModel && isset($userModel->roles_id) ? (int)$userModel->roles_id : null;
        // Aceptar tanto 'admin' como 'administrador' en el nombre del rol (fallback para administradores)
        if (! $canEdit && ($roleId === 1 || ($roleNombre && (
            stripos($roleNombre, 'admin') !== false || stripos($roleNombre, 'administrador') !== false
        )))) {
            $canEdit = true;
        }

        if (! $canEdit) {
            abort(403, 'Acceso no autorizado');
        }
    }

    public function create()
    {
        $this->authorizeAdmin();
        return view('roles.create');
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();
        $data = $request->only(['nombre', 'descripcion', 'permisos']);

        // Normalizar permisos
        $permisosInput = $data['permisos'] ?? null;
        $normalized = [];
        if (is_string($permisosInput)) {
            $normalized = array_filter(array_map('trim', explode(',', $permisosInput)), fn($v) => $v !== '');
        } elseif (is_array($permisosInput)) {
            foreach ($permisosInput as $p) {
                if (!is_string($p)) continue;
                if (strpos($p, ',') !== false) {
                    $parts = array_map('trim', explode(',', $p));
                    foreach ($parts as $part) { if ($part !== '') $normalized[] = $part; }
                } else { if ($p !== '') $normalized[] = trim($p); }
            }
        }
        $data['permisos'] = array_values(array_unique($normalized));

        // Validación básica (sin reglas unique de DB)
        $validator = Validator::make($data, [
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
            'permisos' => 'nullable|array',
            'permisos.*' => 'string',
        ]);

        if ($validator->fails()) {
            if ($request->wantsJson()) return response()->json(['errors' => $validator->errors()], 422);
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Unicidad del nombre: revisar tanto DB como archivo
        $dbExisting = [];
        foreach ($this->loadDbRoles() as $r) { $dbExisting[] = mb_strtolower($r['nombre'] ?? ''); }
        $fileExisting = [];
        foreach ($this->loadRoles() as $r) { $fileExisting[] = mb_strtolower($r['nombre'] ?? ''); }

        $lower = mb_strtolower($data['nombre']);
        if (in_array($lower, $dbExisting) || in_array($lower, $fileExisting)) {
            $err = ['nombre' => ['El nombre del rol ya existe.']];
            if ($request->wantsJson()) return response()->json(['errors' => $err], 422);
            return redirect()->back()->withErrors($err)->withInput();
        }

        // Por compatibilidad mantenemos almacenamiento en archivo como antes
        $roles = $this->loadRoles();
        $new = [
            'id' => $this->nextId($roles),
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? null,
            'permisos' => $data['permisos'] ?? [],
            'source' => 'file',
        ];
        $roles[] = $new;
        $this->saveRoles($roles);

        if ($request->wantsJson()) return response()->json(['data' => (object)$new, 'message' => 'Rol creado correctamente'], 201);
        return redirect()->route('roles.index')->with('success', 'Rol creado correctamente');
    }

    public function show(Request $request, $id)
    {
        // Preferir DB
        $db = RolesModel::find($id);
        if ($db) {
            $rolObj = (object)[
                'id' => $db->id,
                'nombre' => $db->nombre,
                'descripcion' => $db->descripcion,
                'permisos' => $db->permisos ?? [],
                'source' => 'db',
            ];
        } else {
            $roles = $this->loadRoles();
            $rol = $this->findRoleById($roles, $id);
            if (! $rol) {
                if ($request->wantsJson()) return response()->json(['message' => 'Rol no encontrado'], 404);
                abort(404, 'Rol no encontrado');
            }
            $rolObj = (object)$rol;
        }
        if ($request->wantsJson()) return response()->json(['data' => $rolObj], 200);
        return view('roles.show', ['rol' => $rolObj]);
    }

    public function edit($id)
    {
        $this->authorizeAdmin();

        $db = RolesModel::find($id);
        if ($db) {
            return view('roles.edit', ['rol' => (object)[
                'id' => $db->id,
                'nombre' => $db->nombre,
                'descripcion' => $db->descripcion,
                'permisos' => $db->permisos ?? [],
                'source' => 'db',
            ]]);
        }
        $roles = $this->loadRoles();
        $rol = $this->findRoleById($roles, $id);
        if (! $rol) abort(404, 'Rol no encontrado');
        return view('roles.edit', ['rol' => (object)$rol]);
    }

    public function update(Request $request, $id)
    {
        $this->authorizeAdmin();

        // Si existe en DB, actualizar allí
        $db = RolesModel::find($id);
        if ($db) {
            $data = $request->only(['nombre', 'descripcion', 'permisos']);

            // Normalizar permisos
            $permisosInput = $data['permisos'] ?? null;
            $normalized = [];
            if (is_string($permisosInput)) {
                $normalized = array_filter(array_map('trim', explode(',', $permisosInput)), fn($v) => $v !== '');
            } elseif (is_array($permisosInput)) {
                foreach ($permisosInput as $p) {
                    if (!is_string($p)) continue;
                    if (strpos($p, ',') !== false) {
                        $parts = array_map('trim', explode(',', $p));
                        foreach ($parts as $part) { if ($part !== '') $normalized[] = $part; }
                    } else { if ($p !== '') $normalized[] = trim($p); }
                }
            }
            $data['permisos'] = array_values(array_unique($normalized));

            $validator = Validator::make($data, [
                'nombre' => 'required|string|max:255',
                'descripcion' => 'nullable|string|max:1000',
                'permisos' => 'nullable|array',
                'permisos.*' => 'string',
            ]);

            if ($validator->fails()) {
                if ($request->wantsJson()) return response()->json(['errors' => $validator->errors()], 422);
                return redirect()->back()->withErrors($validator)->withInput();
            }

            // Unicidad en DB y archivo
            foreach ($this->loadDbRoles() as $r) { if ((string)$r['id'] !== (string)$id && mb_strtolower($r['nombre'] ?? '') === mb_strtolower($data['nombre'])) {
                    $err = ['nombre' => ['El nombre del rol ya existe.']];
                    if ($request->wantsJson()) return response()->json(['errors' => $err], 422);
                    return redirect()->back()->withErrors($err)->withInput();
                }
            }
            foreach ($this->loadRoles() as $r) { if (mb_strtolower($r['nombre'] ?? '') === mb_strtolower($data['nombre'])) {
                    if ((string)($r['id'] ?? '') !== (string)$id) {
                        $err = ['nombre' => ['El nombre del rol ya existe.']];
                        if ($request->wantsJson()) return response()->json(['errors' => $err], 422);
                        return redirect()->back()->withErrors($err)->withInput();
                    }
                }
            }

            $db->nombre = $data['nombre'];
            $db->descripcion = $data['descripcion'] ?? null;
            $db->permisos = $data['permisos'] ?? [];
            $db->save();

            $updated = [
                'id' => $db->id,
                'nombre' => $db->nombre,
                'descripcion' => $db->descripcion,
                'permisos' => $db->permisos ?? [],
                'source' => 'db',
            ];
            if ($request->wantsJson()) return response()->json(['data' => (object)$updated, 'message' => 'Rol actualizado correctamente'], 200);
            return redirect()->route('roles.index')->with('success', 'Rol actualizado correctamente');
        }

        // Si no existe en DB, continuamos con el flujo de archivo
        $roles = $this->loadRoles();
        $existing = $this->findRoleById($roles, $id);
        if (! $existing) {
            if ($request->wantsJson()) return response()->json(['message' => 'Rol no encontrado'], 404);
            abort(404, 'Rol no encontrado');
        }

        $data = $request->only(['nombre', 'descripcion', 'permisos']);

        // Normalizar permisos
        $permisosInput = $data['permisos'] ?? null;
        $normalized = [];
        if (is_string($permisosInput)) {
            $normalized = array_filter(array_map('trim', explode(',', $permisosInput)), fn($v) => $v !== '');
        } elseif (is_array($permisosInput)) {
            foreach ($permisosInput as $p) {
                if (!is_string($p)) continue;
                if (strpos($p, ',') !== false) {
                    $parts = array_map('trim', explode(',', $p));
                    foreach ($parts as $part) { if ($part !== '') $normalized[] = $part; }
                } else { if ($p !== '') $normalized[] = trim($p); }
            }
        }
        $data['permisos'] = array_values(array_unique($normalized));

        $validator = Validator::make($data, [
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
            'permisos' => 'nullable|array',
            'permisos.*' => 'string',
        ]);

        if ($validator->fails()) {
            if ($request->wantsJson()) return response()->json(['errors' => $validator->errors()], 422);
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Unicidad del nombre (excluyendo el actual)
        foreach ($roles as $r) {
            if ((string)($r['id'] ?? '') === (string)$id) continue;
            if (mb_strtolower($r['nombre'] ?? '') === mb_strtolower($data['nombre'])) {
                $err = ['nombre' => ['El nombre del rol ya existe.']];
                if ($request->wantsJson()) return response()->json(['errors' => $err], 422);
                return redirect()->back()->withErrors($err)->withInput();
            }
        }

        // Actualizar
        foreach ($roles as &$r) {
            if ((string)($r['id'] ?? '') === (string)$id) {
                $r['nombre'] = $data['nombre'];
                $r['descripcion'] = $data['descripcion'] ?? null;
                $r['permisos'] = $data['permisos'] ?? [];
                break;
            }
        }
        unset($r);
        $this->saveRoles($roles);

        $updated = $this->findRoleById($roles, $id);
        if ($request->wantsJson()) return response()->json(['data' => (object)$updated, 'message' => 'Rol actualizado correctamente'], 200);
        return redirect()->route('roles.index')->with('success', 'Rol actualizado correctamente');
    }

    public function destroy(Request $request, $id)
    {
        $this->authorizeAdmin();

        // Si el rol existe en DB, eliminarlo allí
        $db = RolesModel::find($id);
        if ($db) {
            $db->delete();
            if ($request->wantsJson()) return response()->json(['message' => 'Rol eliminado correctamente'], 200);
            return redirect()->route('roles.index')->with('success', 'Rol eliminado correctamente');
        }

        // Si no, eliminar del archivo
        $roles = $this->loadRoles();
        $found = false;
        foreach ($roles as $i => $r) {
            if ((string)($r['id'] ?? '') === (string)$id) {
                array_splice($roles, $i, 1);
                $found = true;
                break;
            }
        }
        if (! $found) {
            if ($request->wantsJson()) return response()->json(['message' => 'Rol no encontrado'], 404);
            abort(404, 'Rol no encontrado');
        }

        $this->saveRoles($roles);
        if ($request->wantsJson()) return response()->json(['message' => 'Rol eliminado correctamente'], 200);
        return redirect()->route('roles.index')->with('success', 'Rol eliminado correctamente');
    }

    public function permisosDisponibles()
    {
        $permisos = [
            'ver_usuarios',
            'crear_usuarios',
            'editar_usuarios',
            'eliminar_usuarios',
            'ver_roles',
            'editar_roles',
        ];

        return response()->json(['data' => $permisos], 200);
    }
}
