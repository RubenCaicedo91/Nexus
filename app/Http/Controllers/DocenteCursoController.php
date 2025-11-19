<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Redirect;
use App\Models\User;
use App\Models\Curso;

class DocenteCursoController extends Controller
{
    protected function authorizeAssign()
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        if (! $user) \abort(403);
        if ($user instanceof User && $user->hasPermission('asignar_docentes')) return true;
        if (\optional($user->role)->nombre && (stripos(\optional($user->role)->nombre, 'admin') !== false || stripos(\optional($user->role)->nombre, 'administrador') !== false)) return true;
        if (isset($user->roles_id) && (int)$user->roles_id === 1) return true;
        \abort(403);
    }

    // Lista de docentes disponibles para asignar cursos
    public function index()
    {
        $this->authorizeAssign();

      
        $docenteRole = null;
        if (class_exists('App\\Models\\RolesModel')) {
            $rolesModel = 'App\\Models\\RolesModel';
            $docenteRole = $rolesModel::where('nombre', 'Docente')->first();
        } elseif (class_exists('App\\Models\\Role')) {
            $roleModel = 'App\\Models\\Role';
            $docenteRole = $roleModel::where('nombre', 'Docente')->first();
        }

        if ($docenteRole) {
            $roleId = $docenteRole->id ?? ($docenteRole->roles_id ?? null);
            if ($roleId) {
                $docentes = User::where('roles_id', $roleId)->get();
            } else {
                $docentes = User::whereHas('role', function($q){ $q->where('nombre', 'LIKE', '%Docente%'); })->get();
            }
        } else {
            $docentes = User::whereHas('role', function($q){ $q->where('nombre', 'LIKE', '%Docente%'); })->get();
        }

        return \view('gestion.docentes_index', compact('docentes'));
    }

    // Formulario para asignar cursos a un docente
    public function edit($docenteId)
    {
        $this->authorizeAssign();

        $docente = User::findOrFail($docenteId);
        $cursos = Curso::all();
    // Evitar ambigüedad en la columna 'id' — pluck del id del modelo Curso
    $cursosAsignados = $docente->cursosAsignados()->pluck('cursos.id')->toArray();

        return \view('gestion.docente_asignar_cursos', compact('docente', 'cursos', 'cursosAsignados'));
    }

    // Guardar asignaciones
    public function update(Request $request, $docenteId)
    {
        $this->authorizeAssign();

        $docente = User::findOrFail($docenteId);

        $validated = $request->validate([
            // Requerir al menos un curso (array con mínimo 1 elemento)
            'cursos' => 'required|array|min:1',
            'cursos.*' => 'exists:cursos,id',
        ]);

        $cursos = $validated['cursos'] ?? [];
        $docente->cursosAsignados()->sync($cursos);

        return \redirect()->route('docentes.index')->with('success', 'Asignaciones actualizadas.');
    }

    // Asignación in-place desde modal: recibe docente_id y cursos[]
    public function assign(Request $request)
    {
        $this->authorizeAssign();

        $validated = $request->validate([
            'docente_id' => 'required|exists:users,id',
            // Requerir al menos un curso cuando se usa el modal de asignación
            'cursos' => 'required|array|min:1',
            'cursos.*' => 'exists:cursos,id',
        ]);

        $docente = User::findOrFail($validated['docente_id']);
        $cursos = $validated['cursos'] ?? [];
        $docente->cursosAsignados()->sync($cursos);

        return \redirect()->back()->with('success', 'Asignaciones guardadas correctamente.');
    }

    // Quitar todas las asignaciones de cursos para un docente
    public function removeAll($docenteId)
    {
        $this->authorizeAssign();

        $docente = User::findOrFail($docenteId);
        $docente->cursosAsignados()->sync([]);

        return \redirect()->route('docentes.edit', $docenteId)->with('success', 'Se quitaron todas las asignaciones del docente.');
    }

    // Endpoint JSON: devuelve cursos asignados a un docente (usado por AJAX)
    public function cursosJson($docenteId)
    {
        // Permitir acceso a cualquier usuario autenticado para uso en filtros UI
        if (!Auth::check()) abort(403);
        $docente = User::findOrFail($docenteId);
        $cursos = $docente->cursosAsignados()->select('id', 'nombre')->get();
        return response()->json($cursos);
    }
}
