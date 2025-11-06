<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Materia;
use App\Models\Curso;
use App\Models\User;
use App\Models\RolesModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MateriasController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $materias = Materia::with(['curso', 'docente'])->orderBy('nombre')->paginate(10);
        return view('materias.index', compact('materias'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $cursos = Curso::orderBy('nombre')->get();
        
        // Obtener solo usuarios con rol de docente
        $rolDocente = RolesModel::where('nombre', 'Docente')->first();
        $docentes = User::where('roles_id', $rolDocente->id ?? 0)->orderBy('name')->get();
        
        return view('materias.create', compact('cursos', 'docentes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:materias,nombre',
            'descripcion' => 'nullable|string|max:1000',
            'curso_id' => 'required|exists:cursos,id',
            'docente_id' => 'nullable|exists:users,id',
        ], [
            'nombre.required' => 'El nombre de la materia es obligatorio.',
            'nombre.unique' => 'Ya existe una materia con este nombre.',
            'curso_id.required' => 'Debe seleccionar un curso.',
            'curso_id.exists' => 'El curso seleccionado no existe.',
            'docente_id.exists' => 'El docente seleccionado no existe.',
        ]);

        try {
            Materia::create($request->all());
            return redirect()->route('materias.index')
                           ->with('success', 'Materia creada exitosamente.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al crear la materia: ' . $e->getMessage()])
                        ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $materia = Materia::with(['curso', 'docente'])->findOrFail($id);
        return view('materias.show', compact('materia'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $materia = Materia::findOrFail($id);
        $cursos = Curso::orderBy('nombre')->get();
        
        // Obtener solo usuarios con rol de docente
        $rolDocente = RolesModel::where('nombre', 'Docente')->first();
        $docentes = User::where('roles_id', $rolDocente->id ?? 0)->orderBy('name')->get();
        
        return view('materias.edit', compact('materia', 'cursos', 'docentes'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $materia = Materia::findOrFail($id);
        
        $request->validate([
            'nombre' => 'required|string|max:255|unique:materias,nombre,' . $id,
            'descripcion' => 'nullable|string|max:1000',
            'curso_id' => 'required|exists:cursos,id',
            'docente_id' => 'nullable|exists:users,id',
        ], [
            'nombre.required' => 'El nombre de la materia es obligatorio.',
            'nombre.unique' => 'Ya existe una materia con este nombre.',
            'curso_id.required' => 'Debe seleccionar un curso.',
            'curso_id.exists' => 'El curso seleccionado no existe.',
            'docente_id.exists' => 'El docente seleccionado no existe.',
        ]);

        try {
            $materia->update($request->all());
            return redirect()->route('materias.index')
                           ->with('success', 'Materia actualizada exitosamente.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al actualizar la materia: ' . $e->getMessage()])
                        ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $materia = Materia::findOrFail($id);
            $nombre = $materia->nombre;
            
            $materia->delete();
            
            return redirect()->route('materias.index')
                           ->with('success', "Materia '{$nombre}' eliminada exitosamente.");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al eliminar la materia: ' . $e->getMessage()]);
        }
    }
}
