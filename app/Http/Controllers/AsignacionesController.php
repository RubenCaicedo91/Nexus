<?php

namespace App\Http\Controllers;

use App\Models\Matricula;
use App\Models\User;
use App\Models\Curso;
use App\Models\Horario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class AsignacionesController extends Controller
{
    /**
     * Display a listing of student assignments with validation status
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
        // If an estudiante_id is provided (from autocomplete selection), filter by user_id (more robust)
        if ($request->filled('estudiante_id')) {
            $query->where('user_id', $request->estudiante_id);
        } elseif ($request->estudiante) {
            // Fallback: search by name text
            $query->whereHas('user', function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->estudiante . '%');
            });
        }
        
        // Filter by status (apply only when a value is selected)
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        // Filter by document completeness (only when a value is selected)
        // Use filled() so an empty "Todos" selection ("") does not apply the filter
        if ($request->filled('documentos_completos') && $request->documentos_completos !== '') {
            $query->where('documentos_completos', $request->documentos_completos);
        }
        
        $asignaciones = $query->orderBy('created_at', 'desc')->paginate(15);
        
        // Get all courses and students for filters
        $cursos = Curso::orderBy('nombre')->get();
        $estudiantes = User::join('roles', 'users.roles_id', '=', 'roles.id')
                          ->where('roles.nombre', '=', 'Estudiante')
                          ->select('users.*')
                          ->orderBy('users.name')
                          ->get();
        
        return view('asignaciones.index', compact('asignaciones', 'cursos', 'estudiantes'));
    }

    /**
     * Show the form for creating a new assignment
     */
    public function create()
    {
        $cursos = Curso::orderBy('nombre')->get();
        
        // Consulta más directa usando join
        $estudiantes = User::join('roles', 'users.roles_id', '=', 'roles.id')
                          ->where('roles.nombre', '=', 'Estudiante')
                          ->select('users.*')
                          ->orderBy('users.name')
                          ->get();
        
        // Debug temporal: agregar información adicional
        $debug_data = [
            'total_estudiantes' => $estudiantes->count(),
            'estudiantes_nombres' => $estudiantes->pluck('name')->toArray(),
            'sql_query' => 'JOIN con roles tabla funcionando'
        ];
        
        return view('asignaciones.create', compact('cursos', 'estudiantes', 'debug_data'));
    }

    /**
     * Store a newly created assignment (only if documents are complete)
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'curso_id' => 'required|exists:cursos,id',
            'fecha_matricula' => 'required|date',
            'estado' => 'required|in:activo,inactivo,suspendido,completado,falta de documentacion',
            'tipo_usuario' => 'required|in:nuevo,antiguo',
            
            // Documentos requeridos
            'documento_identidad' => 'required|file|mimes:pdf,jpg,jpeg,png|max:20480',
            'rh' => 'required|file|mimes:pdf,jpg,jpeg,png|max:20480',
            'certificado_medico' => 'required|file|mimes:pdf,jpg,jpeg,png|max:20480',
            'certificado_notas' => 'required_if:tipo_usuario,antiguo|file|mimes:pdf,jpg,jpeg,png|max:20480',
            'comprobante_pago' => 'required|file|mimes:pdf,jpg,jpeg,png|max:20480',
            
            // Datos de pago
            'monto_pago' => 'required|numeric|min:0',
            'fecha_pago' => 'required|date'
        ]);

        // Check if student is already enrolled in this course
        $exists = Matricula::where('user_id', $request->user_id)
                          ->where('curso_id', $request->curso_id)
                          ->exists();
        
        if ($exists) {
            return back()->withErrors(['user_id' => 'El estudiante ya está asignado a este curso.'])
                        ->withInput();
        }

        // Store uploaded files
        $documentos = [];
        $campos_archivo = ['documento_identidad', 'rh', 'certificado_medico', 'certificado_notas', 'comprobante_pago'];
        
        foreach ($campos_archivo as $campo) {
            if ($request->hasFile($campo)) {
                $path = $request->file($campo)->store('matriculas/' . $request->user_id, 'public');
                $documentos[$campo] = $path;
            }
        }

        // Create matricula with all data
        $data = $request->all();
        $data = array_merge($data, $documentos);
        
        $matricula = Matricula::create($data);
        
        // Update document completion status
        $matricula->actualizarEstadoDocumentos();

        return redirect()->route('asignaciones.index')
                        ->with('success', 'Asignación creada exitosamente con todos los documentos completos.');
    }

    /**
     * Display the specified assignment with course schedule
     */
    public function show(Matricula $asignacion)
    {
        $asignacion->load(['user', 'curso']);
        
        // Get schedule for this course (safely handle missing curso)
        if ($asignacion->curso) {
            $horarios = Horario::where('curso', $asignacion->curso->nombre)->get();
        } else {
            Log::warning('Matricula sin curso asignado', ['matricula_id' => $asignacion->id]);
            $horarios = collect();
        }

        return view('asignaciones.show', compact('asignacion', 'horarios'));
    }

    /**
     * Show the form for editing the specified assignment
     */
    public function edit(Matricula $asignacion)
    {
        $cursos = Curso::orderBy('nombre')->get();
        $estudiantes = User::join('roles', 'users.roles_id', '=', 'roles.id')
                          ->where('roles.nombre', '=', 'Estudiante')
                          ->select('users.*')
                          ->orderBy('users.name')
                          ->get();
        
        return view('asignaciones.edit', compact('asignacion', 'cursos', 'estudiantes'));
    }

    /**
     * Update the specified assignment
     */
    public function update(Request $request, Matricula $asignacion)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'curso_id' => 'required|exists:cursos,id',
            'fecha_matricula' => 'required|date',
            'estado' => 'required|in:activo,inactivo,suspendido,completado,falta de documentacion',
            'tipo_usuario' => 'required|in:nuevo,antiguo',
            
            // Documentos opcionales en edición
            'documento_identidad' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:20480',
            'rh' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:20480',
            'certificado_medico' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:20480',
            'certificado_notas' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:20480',
            'comprobante_pago' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:20480',
            
            // Datos de pago
            'monto_pago' => 'nullable|numeric|min:0',
            'fecha_pago' => 'nullable|date'
        ]);

        // Check if student is already enrolled in this course (excluding current assignment)
        $exists = Matricula::where('user_id', $request->user_id)
                          ->where('curso_id', $request->curso_id)
                          ->where('id', '!=', $asignacion->id)
                          ->exists();
        
        if ($exists) {
            return back()->withErrors(['user_id' => 'El estudiante ya está asignado a este curso.'])
                        ->withInput();
        }

        // En edición: si el tipo de usuario es 'antiguo' y NO existe certificado_notas en la DB
        // y tampoco se está enviando un archivo nuevo, devolver error (requerido)
        if ($request->input('tipo_usuario') === 'antiguo' && empty($asignacion->certificado_notas) && !$request->hasFile('certificado_notas')) {
            return back()->withErrors(['certificado_notas' => 'El certificado de notas es obligatorio para usuarios antiguos.'])
                         ->withInput();
        }

        // Update files if uploaded
        $data = $request->except(['documento_identidad', 'rh', 'certificado_medico', 'certificado_notas', 'comprobante_pago']);
        
        $campos_archivo = ['documento_identidad', 'rh', 'certificado_medico', 'certificado_notas', 'comprobante_pago'];
        
        foreach ($campos_archivo as $campo) {
            if ($request->hasFile($campo)) {
                // Delete old file if exists
                if ($asignacion->$campo) {
                    Storage::disk('public')->delete($asignacion->$campo);
                }
                // Store new file
                $path = $request->file($campo)->store('matriculas/' . $request->user_id, 'public');
                $data[$campo] = $path;
            }
        }

        $asignacion->update($data);
        
        // Update document completion status
        $asignacion->actualizarEstadoDocumentos();

        return redirect()->route('asignaciones.index')
                        ->with('success', 'Asignación actualizada exitosamente.');
    }

    /**
     * Remove the specified assignment
     */
    public function destroy(Matricula $asignacion)
    {
        try {
            // Delete associated files
            $campos_archivo = ['documento_identidad', 'rh', 'certificado_medico', 'certificado_notas', 'comprobante_pago'];
            
            foreach ($campos_archivo as $campo) {
                if ($asignacion->$campo) {
                    Storage::disk('public')->delete($asignacion->$campo);
                }
            }
            
            $asignacion->delete();
            
            return redirect()->route('asignaciones.index')
                            ->with('success', 'Asignación eliminada exitosamente.');
        } catch (\Exception $e) {
            return redirect()->route('asignaciones.index')
                            ->with('error', 'Error al eliminar la asignación.');
        }
    }

    /**
     * Get assignments data as JSON for AJAX requests
     */
    public function getAsignacionesJson(Request $request)
    {
        $query = Matricula::with(['user', 'curso']);
        
        if ($request->curso_id) {
            $query->where('curso_id', $request->curso_id);
        }
        
        $asignaciones = $query->get();
        
        return response()->json($asignaciones);
    }

    /**
     * Devuelve la última matrícula de un estudiante (si existe) con información de curso.
     */
    public function getLatestMatricula($userId)
    {
        $matricula = Matricula::with('curso')
                        ->where('user_id', $userId)
                        ->orderBy('created_at', 'desc')
                        ->first();

        if (! $matricula) {
            return response()->json(null, 204);
        }

        return response()->json([
            'id' => $matricula->id,
            'curso_id' => $matricula->curso_id,
            'curso_asignado_nombre' => $matricula->curso->nombre ?? null,
            // curso_seleccionado es la preferencia guardada al crear la matrícula (curso_nombre)
            'curso_seleccionado' => $matricula->curso_nombre ?? null,
            'fecha_matricula' => $matricula->fecha_matricula,
            'estado' => $matricula->estado,
        ]);
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
        $asignaciones = Matricula::with('user')
                  ->where('curso_id', $cursoId)
                  ->where('estado', 'activo')
                  ->where('documentos_completos', true)
                  ->get();

        // Extraer solo la información necesaria de los usuarios (id y name)
        $estudiantes = $asignaciones->map(function ($matricula) {
            if ($matricula->user) {
                return [
                    'id' => $matricula->user->id,
                    'name' => $matricula->user->name,
                ];
            }
            return null;
        })->filter()->unique('id')->values();

        return response()->json($estudiantes);
    }

    /**
     * Search students by text (names or last names) for autocomplete.
     * Optional query params: q, curso_id
     */
    public function searchStudents(Request $request)
    {
        $q = $request->get('q', '');
        $cursoId = $request->get('curso_id');

        $usersQuery = User::join('roles', 'users.roles_id', '=', 'roles.id')
                    ->where('roles.nombre', '=', 'Estudiante')
                    ->select('users.id', 'users.name', 'users.first_name', 'users.second_name', 'users.first_last', 'users.second_last');

        if (!empty($q)) {
            $usersQuery->where(function ($s) use ($q) {
                $s->where('users.name', 'like', '%' . $q . '%')
                  ->orWhere('users.first_name', 'like', '%' . $q . '%')
                  ->orWhere('users.second_name', 'like', '%' . $q . '%')
                  ->orWhere('users.first_last', 'like', '%' . $q . '%')
                  ->orWhere('users.second_last', 'like', '%' . $q . '%');
            });
        }

        if (!empty($cursoId)) {
            $usersQuery->join('matriculas', 'matriculas.user_id', '=', 'users.id')
                       ->where('matriculas.curso_id', $cursoId)
                       ->where('matriculas.estado', 'activo')
                       ->where('matriculas.documentos_completos', true);
        }

        $users = $usersQuery->orderBy('users.name')->limit(30)->get();

        $result = $users->map(function ($u) {
            $display = $u->name ?: trim(implode(' ', array_filter([$u->first_name, $u->second_name, $u->first_last, $u->second_last])));
            return [
                'id' => $u->id,
                'name' => $display,
            ];
        })->values();

        return response()->json($result);
    }

    /**
     * Validate if assignment can be activated (documents complete)
     */
    public function validateAssignment(Matricula $asignacion)
    {
        $completos = $asignacion->actualizarEstadoDocumentos();
        
        if (!$completos) {
            return response()->json([
                'error' => 'No se puede activar la asignación. Faltan documentos obligatorios.',
                'documentos_faltantes' => $this->getDocumentosFaltantes($asignacion)
            ], 422);
        }
        
        return response()->json([
            'success' => 'Todos los documentos están completos.',
            'documentos_completos' => true
        ]);
    }

    /**
     * Get missing documents list
     */
    private function getDocumentosFaltantes(Matricula $asignacion)
    {
        $documentos = [
            'documento_identidad' => 'Documento de identidad',
            'rh' => 'Tipo de RH',
            'certificado_medico' => 'Certificado médico',
            'certificado_notas' => 'Certificado de notas',
            'comprobante_pago' => 'Comprobante de pago',
        ];
        
        $faltantes = [];
        
        foreach ($documentos as $campo => $nombre) {
            // Si el campo es certificado_notas y el tipo de usuario es 'nuevo', no es obligatorio
            if ($campo === 'certificado_notas' && (($asignacion->tipo_usuario ?? 'nuevo') === 'nuevo')) {
                continue;
            }

            if (empty($asignacion->$campo)) {
                $faltantes[] = $nombre;
            }
        }
        
        if (empty($asignacion->monto_pago) || empty($asignacion->fecha_pago)) {
            $faltantes[] = 'Información de pago completa';
        }
        
        return $faltantes;
    }
}
