<?php

namespace App\Http\Controllers;

use App\Models\Matricula;
use App\Models\User;
use App\Models\Curso;
use App\Models\Horario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MatriculasController extends Controller
{
    /**
     * Display a listing of matriculas with students, courses and schedules
     */
    public function index(Request $request)
    {
        // Build query with eager loading
        $query = Matricula::with(['user', 'curso']);
        
        // Filter by course if specified
        if ($request->curso_id) {
            $query->where('curso_id', $request->curso_id);
        }
        
        // Filter by student name
        if ($request->estudiante) {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->estudiante . '%');
            });
        }
        
        // Filter by status
        if ($request->estado) {
            $query->where('estado', $request->estado);
        }
        
        $matriculas = $query->orderBy('created_at', 'desc')->paginate(15);
        
        // Get all courses and students for filters
        $cursos = Curso::orderBy('nombre')->get();
        $estudiantes = User::whereHas('role', function($q) {
            $q->where('nombre', 'like', '%estudiante%');
        })->orderBy('name')->get();
        
        return view('matriculas.index', compact('matriculas', 'cursos', 'estudiantes'));
    }

    /**
     * Show the form for creating a new matricula
     */
    public function create()
    {
        $cursos = Curso::orderBy('nombre')->get();
        $estudiantes = User::whereHas('role', function($q) {
            $q->where('nombre', 'like', '%estudiante%');
        })->orderBy('name')->get();
        
        return view('matriculas.create', compact('cursos', 'estudiantes'));
    }

    /**
     * Store a newly created matricula
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'curso_id' => 'required|exists:cursos,id',
            'fecha_matricula' => 'required|date',
            'estado' => 'required|in:activo,inactivo,suspendido,completado,falta de documentacion'
        ]);

        // Check if student is already enrolled in this course
        $exists = Matricula::where('user_id', $request->user_id)
                          ->where('curso_id', $request->curso_id)
                          ->exists();
        
        if ($exists) {
            return back()->withErrors(['user_id' => 'El estudiante ya está matriculado en este curso.'])
                        ->withInput();
        }

        Matricula::create($request->all());

        return redirect()->route('matriculas.index')
                        ->with('success', 'Matrícula creada exitosamente.');
    }

    /**
     * Display the specified matricula with course schedule
     */
    public function show(Matricula $matricula)
    {
        $matricula->load(['user', 'curso']);
        
        // Get schedule for this course
        $horarios = Horario::where('curso', $matricula->curso->nombre)->get();
        
        return view('matriculas.show', compact('matricula', 'horarios'));
    }

    /**
     * Show the form for editing the specified matricula
     */
    public function edit(Matricula $matricula)
    {
        $cursos = Curso::orderBy('nombre')->get();
        $estudiantes = User::whereHas('role', function($q) {
            $q->where('nombre', 'like', '%estudiante%');
        })->orderBy('name')->get();
        
        return view('matriculas.edit', compact('matricula', 'cursos', 'estudiantes'));
    }

    /**
     * Update the specified matricula
     */
    public function update(Request $request, Matricula $matricula)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'curso_id' => 'required|exists:cursos,id',
            'fecha_matricula' => 'required|date',
            'estado' => 'required|in:activo,inactivo,suspendido,completado,falta de documentacion'
        ]);

        // Check if student is already enrolled in this course (excluding current matricula)
        $exists = Matricula::where('user_id', $request->user_id)
                          ->where('curso_id', $request->curso_id)
                          ->where('id', '!=', $matricula->id)
                          ->exists();
        
        if ($exists) {
            return back()->withErrors(['user_id' => 'El estudiante ya está matriculado en este curso.'])
                        ->withInput();
        }

        $matricula->update($request->all());

        return redirect()->route('matriculas.index')
                        ->with('success', 'Matrícula actualizada exitosamente.');
    }

    /**
     * Remove the specified matricula
     */
    public function destroy(Matricula $matricula)
    {
        try {
            $matricula->delete();
            return redirect()->route('matriculas.index')
                            ->with('success', 'Matrícula eliminada exitosamente.');
        } catch (\Exception $e) {
            return redirect()->route('matriculas.index')
                            ->with('error', 'Error al eliminar la matrícula.');
        }
    }

    /**
     * Get matriculas data as JSON for AJAX requests
     */
    public function getMatriculasJson(Request $request)
    {
        $query = Matricula::with(['user', 'curso']);
        
        if ($request->curso_id) {
            $query->where('curso_id', $request->curso_id);
        }
        
        $matriculas = $query->get();
        
        return response()->json($matriculas);
    }

    /**
     * Get course schedule as JSON
     */
    public function getCourseSchedule($cursoId)
    {
        $curso = Curso::findOrFail($cursoId);
        $horarios = Horario::where('curso', $curso->nombre)->get();
        
        return response()->json([
            'curso' => $curso,
            'horarios' => $horarios
        ]);
    }

    /**
     * Get students enrolled in a specific course
     */
    public function getStudentsByCourse($cursoId)
    {
        $matriculas = Matricula::with('user')
                              ->where('curso_id', $cursoId)
                              ->where('estado', 'activa')
                              ->get();
        
        return response()->json($matriculas);
    }
}