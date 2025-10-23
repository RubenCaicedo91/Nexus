<?php

namespace App\Http\Controllers;

use App\Models\Matricula;
use App\Models\User; // Assuming students are users
use App\Models\RolesModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MatriculaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $matriculas = Matricula::with('user')->get();
        return view('matriculas.index', compact('matriculas'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Obtener dinámicamente el id del rol 'Estudiante' en lugar de usar un id fijo
        $studentRole = RolesModel::where('nombre', 'Estudiante')->first();
        if ($studentRole) {
            $students = User::where('roles_id', $studentRole->id)->get();
        } else {
            // Si no existe el rol, devolver colección vacía para evitar errores en la vista
            $students = collect();
        }

        return view('matriculas.create', compact('students'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'fecha_matricula' => 'required|date',
            'estado' => 'required|string|max:255',
        ]);

        Matricula::create($request->all());

        return redirect()->route('matriculas.index')
                         ->with('success', 'Matrícula creada exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Matricula $matricula)
    {
        return view('matriculas.show', compact('matricula'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Matricula $matricula)
    {
        // Obtener dinámicamente el id del rol 'Estudiante'
        $studentRole = RolesModel::where('nombre', 'Estudiante')->first();
        if ($studentRole) {
            $students = User::where('roles_id', $studentRole->id)->get();
        } else {
            $students = collect();
        }

        return view('matriculas.edit', compact('matricula', 'students'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Matricula $matricula)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'fecha_matricula' => 'required|date',
            'estado' => 'required|string|max:255',
        ]);

        $matricula->update($request->all());

        return redirect()->route('matriculas.index')
                         ->with('success', 'Matrícula actualizada exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Matricula $matricula)
    {
        $matricula->delete();

        return redirect()->route('matriculas.index')
                         ->with('success', 'Matrícula eliminada exitosamente.');
    }
}
