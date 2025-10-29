<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Curso;
use App\Models\RolesModel;
use Illuminate\Support\Facades\Auth;

class DocenteCursoController extends Controller
{
    protected function authorizeAssign()
    {
        $user = Auth::user();
        if (! $user) abort(403);
        if ($user instanceof \App\Models\User && $user->hasPermission('asignar_docentes')) return true;
        if (optional($user->role)->nombre && (stripos(optional($user->role)->nombre, 'admin') !== false || stripos(optional($user->role)->nombre, 'administrador') !== false)) return true;
        if (isset($user->roles_id) && (int)$user->roles_id === 1) return true;
        abort(403);
    }

    // Lista de docentes disponibles para asignar cursos
    public function index()
    {
        $this->authorizeAssign();

        // Obtener rol Docente
        $docenteRole = RolesModel::where('nombre', 'Docente')->first();
        if ($docenteRole) {
            $docentes = User::where('roles_id', $docenteRole->id)->get();
        } else {
            $docentes = User::whereHas('role', function($q){ $q->where('nombre', 'LIKE', '%Docente%'); })->get();
        }

        return view('gestion.docentes_index', compact('docentes'));
    }

    // Formulario para asignar cursos a un docente
    public function edit($docenteId)
    {
        $this->authorizeAssign();

        $docente = User::findOrFail($docenteId);
        $cursos = Curso::all();
    // Evitar ambigüedad en la columna 'id' especificando la tabla cursos
    $cursosAsignados = $docente->cursosAsignados()->pluck('cursos.id')->toArray();

        return view('gestion.docente_asignar_cursos', compact('docente', 'cursos', 'cursosAsignados'));
    }

    // Guardar asignaciones
    public function update(Request $request, $docenteId)
    {
        $this->authorizeAssign();

        $docente = User::findOrFail($docenteId);

        $validated = $request->validate([
            'cursos' => 'nullable|array',
            'cursos.*' => 'exists:cursos,id',
        ]);

        $cursos = $validated['cursos'] ?? [];
        $docente->cursosAsignados()->sync($cursos);

        return redirect()->route('docentes.index')->with('success', 'Asignaciones actualizadas.');
    }

    // Asignación in-place desde modal: recibe docente_id y cursos[]
    public function assign(Request $request)
    {
        $this->authorizeAssign();

        $validated = $request->validate([
            'docente_id' => 'required|exists:users,id',
            'cursos' => 'nullable|array',
            'cursos.*' => 'exists:cursos,id',
        ]);

        $docente = User::findOrFail($validated['docente_id']);
        $cursos = $validated['cursos'] ?? [];
        $docente->cursosAsignados()->sync($cursos);

        return redirect()->back()->with('success', 'Asignaciones guardadas correctamente.');
    }
}
