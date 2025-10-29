<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Materia;
use App\Models\Curso;
use App\Models\User;
use App\Models\RolesModel;
use Illuminate\Support\Facades\Auth;

class MateriaController extends Controller
{
    protected function authorizeAssign()
    {
        $user = Auth::user();
        if (! $user) abort(403);
        if ($user instanceof \App\Models\User && $user->hasPermission('asignar_docentes')) return true;
        // fallback admin
        if (optional($user->role)->nombre && (stripos(optional($user->role)->nombre, 'admin') !== false || stripos(optional($user->role)->nombre, 'administrador') !== false)) return true;
        if (isset($user->roles_id) && (int)$user->roles_id === 1) return true;
        abort(403);
    }

    // Mostrar materias para un curso y permitir asignar/crear
    public function index($cursoId)
    {
        $this->authorizeAssign();

        $curso = Curso::findOrFail($cursoId);
        $materias = Materia::where('curso_id', $curso->id)->get();

        // Obtener lista de docentes (por rol)
        $docenteRole = RolesModel::where('nombre', 'Docente')->first();
        $docentes = [];
        if ($docenteRole) {
            $docentes = User::where('roles_id', $docenteRole->id)->get();
        } else {
            // fallback: buscar usuarios cuyo role nombre contenga 'docente'
            $docentes = User::whereHas('role', function($q){ $q->where('nombre', 'LIKE', '%Docente%'); })->get();
        }

        return view('gestion.materias_index', compact('curso', 'materias', 'docentes'));
    }

    // Guardar nueva materia para un curso
    public function store(Request $request, $cursoId)
    {
        $this->authorizeAssign();

        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
        ]);

        $curso = Curso::findOrFail($cursoId);

        Materia::create([
            'nombre' => $request->input('nombre'),
            'descripcion' => $request->input('descripcion'),
            'curso_id' => $curso->id,
            'docente_id' => null,
        ]);

        return redirect()->route('cursos.materias', $curso->id)->with('success', 'Materia creada correctamente.');
    }

    // Formulario para editar asignación de docente
    public function edit($id)
    {
        $this->authorizeAssign();

        $materia = Materia::findOrFail($id);
        $curso = $materia->curso;

        $docenteRole = RolesModel::where('nombre', 'Docente')->first();
        $docentes = [];
        if ($docenteRole) {
            $docentes = User::where('roles_id', $docenteRole->id)->get();
        } else {
            $docentes = User::whereHas('role', function($q){ $q->where('nombre', 'LIKE', '%Docente%'); })->get();
        }

        return view('gestion.editar_materia', compact('materia', 'curso', 'docentes'));
    }

    // Actualizar la materia (asignar docente)
    public function update(Request $request, $id)
    {
        $this->authorizeAssign();

        $materia = Materia::findOrFail($id);

        $request->validate([
            'docente_id' => 'nullable|exists:users,id',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
        ]);

        $materia->nombre = $request->input('nombre');
        $materia->descripcion = $request->input('descripcion');
        $materia->docente_id = $request->input('docente_id');
        $materia->save();

        return redirect()->route('cursos.materias', $materia->curso_id)->with('success', 'Materia actualizada correctamente.');
    }
}
