<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
        $roles = $this->loadRoles();
        usort($roles, function ($a, $b) { return ($a['id'] ?? 0) <=> ($b['id'] ?? 0); });

        // Convertir arrays a objetos para compatibilidad con vistas que usan ->
        $rolesObj = array_map(fn($r) => (object)$r, $roles);

        if ($request->wantsJson()) {
            return response()->json(['data' => $rolesObj], 200);
        }

        return view('roles.index', ['roles' => $rolesObj]);
    }

    public function create()
    {
        return view('roles.create');
    }

    public function store(Request $request)
    {
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

        // Unicidad del nombre a nivel de archivo
        $roles = $this->loadRoles();
        foreach ($roles as $r) {
            if (mb_strtolower($r['nombre'] ?? '') === mb_strtolower($data['nombre'])) {
                $err = ['nombre' => ['El nombre del rol ya existe.']];
                if ($request->wantsJson()) return response()->json(['errors' => $err], 422);
                return redirect()->back()->withErrors($err)->withInput();
            }
        }

        $new = [
            'id' => $this->nextId($roles),
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? null,
            'permisos' => $data['permisos'] ?? [],
        ];

        $roles[] = $new;
        $this->saveRoles($roles);

        if ($request->wantsJson()) return response()->json(['data' => (object)$new, 'message' => 'Rol creado correctamente'], 201);
        return redirect()->route('roles.index')->with('success', 'Rol creado correctamente');
    }

    public function show(Request $request, $id)
    {
        $roles = $this->loadRoles();
        $rol = $this->findRoleById($roles, $id);
        if (! $rol) {
            if ($request->wantsJson()) return response()->json(['message' => 'Rol no encontrado'], 404);
            abort(404, 'Rol no encontrado');
        }

        $rolObj = (object)$rol;
        if ($request->wantsJson()) return response()->json(['data' => $rolObj], 200);
        return view('roles.show', ['rol' => $rolObj]);
    }

    public function edit($id)
    {
        $roles = $this->loadRoles();
        $rol = $this->findRoleById($roles, $id);
        if (! $rol) abort(404, 'Rol no encontrado');
        return view('roles.edit', ['rol' => (object)$rol]);
    }

    public function update(Request $request, $id)
    {
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
