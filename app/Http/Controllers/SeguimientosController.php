<?php

namespace App\Http\Controllers;

use App\Models\Seguimiento;
use App\Models\User;
use App\Models\Cita;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SeguimientosController extends Controller
{
    /**
     * Constructor
     */
    public function __construct()
    {
        // Middleware se define en las rutas
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Seguimiento::with(['estudiante', 'responsable', 'cita']);
        
        // Filtros
        if ($request->filled('estudiante_id')) {
            $query->where('estudiante_id', $request->estudiante_id);
        }
        
        if ($request->filled('tipo_seguimiento')) {
            $query->where('tipo_seguimiento', $request->tipo_seguimiento);
        }
        
        if ($request->filled('estado_seguimiento')) {
            $query->where('estado_seguimiento', $request->estado_seguimiento);
        }
        
        if ($request->filled('nivel_gravedad')) {
            $query->where('nivel_gravedad', $request->nivel_gravedad);
        }
        
        if ($request->filled('responsable_id')) {
            $query->where('responsable_id', $request->responsable_id);
        }
        
        if ($request->filled('fecha_desde')) {
            $fechaBase = $request->filled('usar_fecha_identificacion') ? 'fecha_identificacion' : 'fecha';
            $query->where($fechaBase, '>=', $request->fecha_desde);
        }
        
        if ($request->filled('fecha_hasta')) {
            $fechaBase = $request->filled('usar_fecha_identificacion') ? 'fecha_identificacion' : 'fecha';
            $query->where($fechaBase, '<=', $request->fecha_hasta);
        }
        
        if ($request->filled('requiere_revision')) {
            $query->requierenRevision();
        }
        
        if ($request->filled('gravedad_alta')) {
            $query->gravedadAlta();
        }
        
        if ($request->filled('solo_activos')) {
            $query->activos();
        }
        
        if ($request->filled('buscar')) {
            $query->where(function($q) use ($request) {
                $buscar = $request->buscar;
                $q->where('titulo', 'LIKE', "%{$buscar}%")
                  ->orWhere('descripcion_situacion', 'LIKE', "%{$buscar}%")
                  ->orWhere('observaciones', 'LIKE', "%{$buscar}%")
                  ->orWhereHas('estudiante', function($subQ) use ($buscar) {
                      $subQ->where('name', 'LIKE', "%{$buscar}%");
                  });
            });
        }
        
        // Filtro por rol del usuario actual
        $user = Auth::user();
        if ($user->roles->nombre === 'Docente') {
            // Los docentes ven seguimientos de sus estudiantes o los que ellos crearon
            $query->where(function($q) use ($user) {
                $q->where('responsable_id', $user->id)
                  ->orWhereHas('estudiante', function($subQ) use ($user) {
                      // Aquí se podría agregar lógica para estudiantes asignados al docente
                  });
            });
        } elseif ($user->roles->nombre === 'Acudiente') {
            // Los acudientes solo ven seguimientos de sus hijos
            $estudiantesAcudiente = User::where('acudiente_id', $user->id)->pluck('id');
            $query->whereIn('estudiante_id', $estudiantesAcudiente);
        }
        
        $seguimientos = $query->orderBy('created_at', 'desc')->paginate(15);
        
        // Datos para filtros
        $estudiantes = User::join('roles', 'users.roles_id', '=', 'roles.id')
                          ->where('roles.nombre', 'Estudiante')
                          ->select('users.*')
                          ->orderBy('users.name')
                          ->get();
        
        $responsables = User::join('roles', 'users.roles_id', '=', 'roles.id')
                           ->whereIn('roles.nombre', ['orientador', 'Docente', 'Coordinador Académico'])
                           ->select('users.*')
                           ->orderBy('users.name')
                           ->get();
        
        // Estadísticas
        $estadisticas = Seguimiento::reporteEstadisticas();
        
        return view('seguimientos.index', compact('seguimientos', 'estudiantes', 'responsables', 'estadisticas'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        // Obtener estudiantes
        $estudiantes = User::join('roles', 'users.roles_id', '=', 'roles.id')
                          ->where('roles.nombre', 'Estudiante')
                          ->select('users.*')
                          ->orderBy('users.name')
                          ->get();
        
        // Obtener responsables potenciales
        $responsables = User::join('roles', 'users.roles_id', '=', 'roles.id')
                           ->whereIn('roles.nombre', ['orientador', 'Docente', 'Coordinador Académico'])
                           ->select('users.*')
                           ->orderBy('users.name')
                           ->get();
        
        // Obtener citas si viene desde una cita específica
        $citaId = $request->get('cita_id');
        $cita = $citaId ? Cita::find($citaId) : null;
        
        return view('seguimientos.create', compact('estudiantes', 'responsables', 'cita'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'estudiante_id' => 'required|exists:users,id',
            'cita_id' => 'nullable|exists:citas,id',
            'tipo_seguimiento' => 'required|in:academico,disciplinario,psicologico,familiar,vocacional,convivencia,adaptacion',
            'area_enfoque' => 'nullable|in:rendimiento_academico,comportamiento,asistencia,participacion,relaciones_interpersonales,desarrollo_emocional,orientacion_vocacional,situacion_familiar,adaptacion_escolar',
            'titulo' => 'required|string|max:200',
            'descripcion_situacion' => 'required|string',
            'observaciones' => 'nullable|string',
            'observaciones_comportamiento' => 'nullable|string',
            'nivel_gravedad' => 'required|in:bajo,medio,alto,critico',
            'estado_seguimiento' => 'required|in:activo,en_proceso,pausado,completado,derivado',
            'fecha_identificacion' => 'required|date|before_or_equal:today',
            'fecha_primera_intervencion' => 'nullable|date|after_or_equal:fecha_identificacion',
            'fecha_proxima_revision' => 'nullable|date|after:today',
            'padres_informados' => 'boolean',
            'fecha_comunicacion_padres' => 'nullable|date|before_or_equal:today',
            'respuesta_padres' => 'nullable|string',
            'requiere_atencion_especializada' => 'boolean',
            'derivado_a' => 'nullable|string|max:255',
            'confidencial' => 'boolean',
            'plan_accion' => 'nullable|string',
            'recursos_utilizados' => 'nullable|string',
            'notas_adicionales' => 'nullable|string'
        ]);

        // Validaciones adicionales
        if ($validated['padres_informados'] && empty($validated['fecha_comunicacion_padres'])) {
            return back()->withErrors(['fecha_comunicacion_padres' => 'La fecha de comunicación es obligatoria si los padres fueron informados.']);
        }

        if ($validated['requiere_atencion_especializada'] && empty($validated['derivado_a'])) {
            return back()->withErrors(['derivado_a' => 'Debe especificar a dónde se deriva si requiere atención especializada.']);
        }

        // Asignar responsable actual
        $validated['responsable_id'] = Auth::id();
        
        // Copiar fecha a campo de compatibilidad
        $validated['fecha'] = $validated['fecha_identificacion'];

        $seguimiento = Seguimiento::create($validated);

        // Si viene de una cita, actualizar la cita para requerir seguimiento
        if ($seguimiento->cita_id) {
            $seguimiento->cita->update([
                'requiere_seguimiento' => true,
                'fecha_seguimiento' => $validated['fecha_proxima_revision']
            ]);
        }

        return redirect()->route('seguimientos.show', $seguimiento)
                        ->with('success', 'Seguimiento creado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Seguimiento $seguimiento)
    {
        // Verificar permisos
        $user = Auth::user();
        if ($user->roles->nombre === 'Acudiente') {
            // Verificar que el acudiente puede ver este seguimiento
            $estudiantesAcudiente = User::where('acudiente_id', $user->id)->pluck('id');
            if (!$estudiantesAcudiente->contains($seguimiento->estudiante_id)) {
                abort(403, 'No tienes permisos para ver este seguimiento.');
            }
        } elseif ($user->roles->nombre === 'Docente') {
            // Verificar que el docente puede ver este seguimiento
            if ($seguimiento->responsable_id !== $user->id) {
                abort(403, 'No tienes permisos para ver este seguimiento.');
            }
        }

        $seguimiento->load(['estudiante', 'responsable', 'cita']);
        
        return view('seguimientos.show', compact('seguimiento'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Seguimiento $seguimiento)
    {
        // Solo el responsable o administradores pueden editar
        $user = Auth::user();
        if ($seguimiento->responsable_id !== $user->id && !in_array($user->roles->nombre, ['Rector', 'orientador'])) {
            abort(403, 'No tienes permisos para editar este seguimiento.');
        }

        if (!$seguimiento->puedeSerEditado()) {
            return redirect()->route('seguimientos.show', $seguimiento)
                            ->with('error', 'No se puede editar un seguimiento en estado: ' . $seguimiento->estado_seguimiento_formateado);
        }

        $estudiantes = User::join('roles', 'users.roles_id', '=', 'roles.id')
                          ->where('roles.nombre', 'Estudiante')
                          ->select('users.*')
                          ->orderBy('users.name')
                          ->get();
        
        $responsables = User::join('roles', 'users.roles_id', '=', 'roles.id')
                           ->whereIn('roles.nombre', ['orientador', 'Docente', 'Coordinador Académico'])
                           ->select('users.*')
                           ->orderBy('users.name')
                           ->get();

        return view('seguimientos.edit', compact('seguimiento', 'estudiantes', 'responsables'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Seguimiento $seguimiento)
    {
        // Verificar permisos
        $user = Auth::user();
        if ($seguimiento->responsable_id !== $user->id && !in_array($user->roles->nombre, ['Rector', 'orientador'])) {
            abort(403, 'No tienes permisos para editar este seguimiento.');
        }

        $validated = $request->validate([
            'estudiante_id' => 'required|exists:users,id',
            'tipo_seguimiento' => 'required|in:academico,disciplinario,psicologico,familiar,vocacional,convivencia,adaptacion',
            'area_enfoque' => 'nullable|in:rendimiento_academico,comportamiento,asistencia,participacion,relaciones_interpersonales,desarrollo_emocional,orientacion_vocacional,situacion_familiar,adaptacion_escolar',
            'titulo' => 'required|string|max:200',
            'descripcion_situacion' => 'required|string',
            'observaciones' => 'nullable|string',
            'observaciones_comportamiento' => 'nullable|string',
            'nivel_gravedad' => 'required|in:bajo,medio,alto,critico',
            'estado_seguimiento' => 'required|in:activo,en_proceso,pausado,completado,derivado',
            'fecha_identificacion' => 'required|date|before_or_equal:today',
            'fecha_primera_intervencion' => 'nullable|date|after_or_equal:fecha_identificacion',
            'fecha_proxima_revision' => 'nullable|date|after:today',
            'acciones_realizadas' => 'nullable|string',
            'recomendaciones' => 'nullable|string',
            'plan_accion' => 'nullable|string',
            'recursos_utilizados' => 'nullable|string',
            'padres_informados' => 'boolean',
            'fecha_comunicacion_padres' => 'nullable|date|before_or_equal:today',
            'respuesta_padres' => 'nullable|string',
            'logros_alcanzados' => 'nullable|string',
            'dificultades_encontradas' => 'nullable|string',
            'nivel_mejora' => 'nullable|in:ninguna,leve,moderada,significativa',
            'evaluacion_final' => 'nullable|string',
            'requiere_atencion_especializada' => 'boolean',
            'derivado_a' => 'nullable|string|max:255',
            'confidencial' => 'boolean',
            'notas_adicionales' => 'nullable|string'
        ]);

        // Actualizar fecha de compatibilidad
        $validated['fecha'] = $validated['fecha_identificacion'];

        $seguimiento->update($validated);

        return redirect()->route('seguimientos.show', $seguimiento)
                        ->with('success', 'Seguimiento actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Seguimiento $seguimiento)
    {
        // Solo el responsable o administradores pueden eliminar
        $user = Auth::user();
        if ($seguimiento->responsable_id !== $user->id && !in_array($user->roles->nombre, ['Rector'])) {
            abort(403, 'No tienes permisos para eliminar este seguimiento.');
        }

        $seguimiento->delete();

        return redirect()->route('seguimientos.index')
                        ->with('success', 'Seguimiento eliminado exitosamente.');
    }

    /**
     * Registrar una nueva sesión de seguimiento
     */
    public function registrarSesion(Request $request, Seguimiento $seguimiento)
    {
        $user = Auth::user();
        if ($seguimiento->responsable_id !== $user->id && !in_array($user->roles->nombre, ['Rector', 'orientador'])) {
            abort(403, 'No tienes permisos para registrar sesiones en este seguimiento.');
        }

        $validated = $request->validate([
            'observaciones_sesion' => 'required|string',
            'acciones_realizadas' => 'nullable|string',
            'fecha_proxima_revision' => 'nullable|date|after:today'
        ]);

        $seguimiento->registrarSesion(
            $validated['observaciones_sesion'],
            $validated['acciones_realizadas'] ?? null
        );

        if (!empty($validated['fecha_proxima_revision'])) {
            $seguimiento->programarRevision($validated['fecha_proxima_revision']);
        }

        return redirect()->route('seguimientos.show', $seguimiento)
                        ->with('success', 'Sesión registrada exitosamente.');
    }

    /**
     * Cambiar estado del seguimiento
     */
    public function cambiarEstado(Request $request, Seguimiento $seguimiento)
    {
        $user = Auth::user();
        if ($seguimiento->responsable_id !== $user->id && !in_array($user->roles->nombre, ['Rector', 'orientador'])) {
            abort(403, 'No tienes permisos para cambiar el estado de este seguimiento.');
        }

        $validated = $request->validate([
            'nuevo_estado' => 'required|in:activo,en_proceso,pausado,completado,derivado',
            'motivo' => 'nullable|string',
            'evaluacion_final' => 'nullable|string',
            'logros_alcanzados' => 'nullable|string',
            'derivado_a' => 'nullable|string|required_if:nuevo_estado,derivado'
        ]);

        switch ($validated['nuevo_estado']) {
            case 'en_proceso':
                $seguimiento->marcarEnProceso($validated['motivo'] ?? null);
                break;
            case 'pausado':
                $seguimiento->pausar($validated['motivo']);
                break;
            case 'completado':
                $seguimiento->completar(
                    $validated['evaluacion_final'] ?? null,
                    $validated['logros_alcanzados'] ?? null
                );
                break;
            case 'derivado':
                $seguimiento->derivar($validated['derivado_a'], $validated['motivo'] ?? null);
                break;
            default:
                $seguimiento->update(['estado_seguimiento' => $validated['nuevo_estado']]);
        }

        return redirect()->route('seguimientos.show', $seguimiento)
                        ->with('success', 'Estado del seguimiento actualizado exitosamente.');
    }

    /**
     * Informar a los padres
     */
    public function informarPadres(Request $request, Seguimiento $seguimiento)
    {
        $validated = $request->validate([
            'fecha_comunicacion_padres' => 'required|date|before_or_equal:today',
            'respuesta_padres' => 'nullable|string'
        ]);

        $seguimiento->informarPadres(
            $validated['fecha_comunicacion_padres'],
            $validated['respuesta_padres'] ?? null
        );

        return redirect()->route('seguimientos.show', $seguimiento)
                        ->with('success', 'Comunicación con padres registrada exitosamente.');
    }

    /**
     * Vista de dashboard con estadísticas
     */
    public function dashboard()
    {
        $user = Auth::user();
        
        // Estadísticas generales
        $estadisticas = Seguimiento::reporteEstadisticas();
        
        // Seguimientos que requieren atención inmediata
        $query = Seguimiento::with(['estudiante', 'responsable']);
        
        if ($user->roles->nombre === 'Docente') {
            $query->where('responsable_id', $user->id);
        } elseif ($user->roles->nombre === 'Acudiente') {
            $estudiantesAcudiente = User::where('acudiente_id', $user->id)->pluck('id');
            $query->whereIn('estudiante_id', $estudiantesAcudiente);
        }
        
        $urgentes = $query->gravedadAlta()->activos()->limit(10)->get();
        $requierenRevision = $query->requierenRevision()->limit(10)->get();
        $recientes = $query->orderBy('created_at', 'desc')->limit(10)->get();
        
        return view('seguimientos.dashboard', compact(
            'estadisticas', 
            'urgentes', 
            'requierenRevision', 
            'recientes'
        ));
    }

    /**
     * Reporte de seguimientos por estudiante
     */
    public function reporteEstudiante(User $estudiante)
    {
        $seguimientos = Seguimiento::porEstudiante($estudiante->id)
                                  ->with(['responsable', 'cita'])
                                  ->orderBy('created_at', 'desc')
                                  ->get();
        
        return view('seguimientos.reporte-estudiante', compact('estudiante', 'seguimientos'));
    }

    /**
     * API para obtener seguimientos en formato JSON
     */
    public function apiSeguimientos(Request $request)
    {
        $query = Seguimiento::with(['estudiante', 'responsable']);
        
        // Aplicar filtros de la request
        if ($request->filled('estudiante_id')) {
            $query->where('estudiante_id', $request->estudiante_id);
        }
        
        if ($request->filled('tipo')) {
            $query->where('tipo_seguimiento', $request->tipo);
        }
        
        $seguimientos = $query->get();
        
        return response()->json([
            'seguimientos' => $seguimientos,
            'estadisticas' => Seguimiento::reporteEstadisticas()
        ]);
    }
}