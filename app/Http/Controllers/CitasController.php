<?php

namespace App\Http\Controllers;

use App\Models\Cita;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CitasController extends Controller
{
    /**
     * Constructor - Middleware de autenticación
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
        $query = Cita::with(['solicitante', 'orientador', 'estudianteReferido']);
        
        // Filtros
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }
        
        if ($request->filled('orientador_id')) {
            $query->where('orientador_id', $request->orientador_id);
        }
        
        if ($request->filled('fecha_desde')) {
            $query->where('fecha_asignada', '>=', $request->fecha_desde);
        }
        
        if ($request->filled('fecha_hasta')) {
            $query->where('fecha_asignada', '<=', $request->fecha_hasta);
        }
        
        if ($request->filled('tipo_cita')) {
            $query->where('tipo_cita', $request->tipo_cita);
        }
        
        if ($request->filled('prioridad')) {
            $query->where('prioridad', $request->prioridad);
        }
        
        // Filtro por rol del usuario actual
        $user = Auth::user();
        $roleName = strtolower($user->roles->nombre ?? ($user->role->nombre ?? ''));
        if ($roleName === 'acudiente') {
            // Los acudientes ven las citas que ellos mismos solicitaron y las que están a nombre de sus estudiantes
            $studentIds = $user->acudientes()->pluck('id')->toArray();
            $query->where(function($q) use ($user, $studentIds) {
                $q->where('solicitante_id', $user->id)
                  ->orWhereIn('estudiante_referido_id', $studentIds ?: [0]);
            });
        } elseif ($roleName === 'orientador') {
            // Los orientadores ven todas las solicitudes (sin filtrar)
            // no añadimos condiciones para que vean todo
        } else {
            // Otros roles solo ven sus propias citas
            $query->where('solicitante_id', $user->id);
        }
        
        $citas = $query->orderBy('created_at', 'desc')
                  ->paginate(15);
        
        // Datos para filtros
        $orientadores = User::join('roles', 'users.roles_id', '=', 'roles.id')
                           ->where('roles.nombre', 'Orientador')
                           ->select('users.*')
                           ->get();
        
        return view('citas.index', compact('citas', 'orientadores'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Obtener orientadores disponibles
        $orientadores = User::join('roles', 'users.roles_id', '=', 'roles.id')
                           ->where('roles.nombre', 'Orientador')
                           ->select('users.*')
                           ->orderBy('users.name')
                           ->get();
        
        // Obtener estudiantes
        $estudiantes = User::join('roles', 'users.roles_id', '=', 'roles.id')
                          ->where('roles.nombre', 'Estudiante')
                          ->select('users.*')
                          ->orderBy('users.name')
                          ->get();
        
        return view('citas.create', compact('orientadores', 'estudiantes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'estudiante_referido_id' => 'nullable|exists:users,id',
            'orientador_id' => 'nullable|exists:users,id',
            'tipo_cita' => 'required|in:orientacion,psicologica,disciplinaria,familiar,seguimiento,vocacional',
            'modalidad' => 'required|in:presencial,virtual,telefonica',
            'motivo' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'observaciones_previas' => 'nullable|string',
            'fecha_solicitada' => 'required|date|after_or_equal:today',
            'hora_solicitada' => 'required|date_format:H:i',
            'duracion_estimada' => 'required|integer|min:15|max:180',
            'prioridad' => 'required|in:baja,media,alta,urgente',
            'lugar_cita' => 'nullable|string|max:255',
            'link_virtual' => 'nullable|url',
            'instrucciones_adicionales' => 'nullable|string'
        ]);

        // Validaciones adicionales
        if ($validated['modalidad'] === 'virtual' && empty($validated['link_virtual'])) {
            return back()->withErrors(['link_virtual' => 'El link virtual es obligatorio para citas virtuales.']);
        }

        if ($validated['modalidad'] === 'presencial' && empty($validated['lugar_cita'])) {
            return back()->withErrors(['lugar_cita' => 'El lugar de la cita es obligatorio para citas presenciales.']);
        }

        // Verificar disponibilidad del orientador (si se especifica)
        if (!empty($validated['orientador_id'])) {
            $conflicto = Cita::where('orientador_id', $validated['orientador_id'])
                             ->where('fecha_asignada', $validated['fecha_solicitada'])
                             ->where('hora_asignada', $validated['hora_solicitada'])
                             ->whereIn('estado', ['programada', 'confirmada', 'en_curso'])
                             ->exists();
            
            if ($conflicto) {
                return back()->withErrors(['orientador_id' => 'El orientador ya tiene una cita programada en esa fecha y hora.']);
            }
        }

        $validated['solicitante_id'] = Auth::id();
        $validated['estado'] = 'solicitada';

        $cita = Cita::create($validated);

        return redirect()->route('citas.show', $cita)
                        ->with('success', 'Cita solicitada exitosamente. En breve recibirás confirmación.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Cita $cita)
    {
        // Verificar permisos
        $user = Auth::user();
        $roleName = strtolower($user->roles->nombre ?? ($user->role->nombre ?? ''));

        // Permitir si es orientador
        if ($roleName === 'orientador') {
            // ok
        } else {
            // Permitir si es el solicitante declarado
            if ($cita->solicitante_id === $user->id) {
                // ok
            } else {
                // Permitir si el usuario es el acudiente del estudiante referido
                $esAcudienteDelEstudiante = optional($cita->estudianteReferido)->acudiente_id === $user->id;
                // Permitir si el usuario es el estudiante referido
                $esEstudianteReferido = optional($cita->estudianteReferido)->id === $user->id;

                if (! $esAcudienteDelEstudiante && ! $esEstudianteReferido) {
                    abort(403, 'No tienes permisos para ver esta cita.');
                }
            }
        }

        $with = ['solicitante', 'orientador', 'estudianteReferido', 'canceladoPor'];
        if (\Illuminate\Support\Facades\Schema::hasColumn('citas', 'parent_cita_id')) {
            $with[] = 'children';
            $with[] = 'parent';
        }
        $cita->load($with);
        
        return view('citas.show', compact('cita'));
    }

    /**
     * Ajax: retornar partial HTML con la revisión/observaciones de la cita.
     */
    public function revision(Cita $cita)
    {
        // Reusar las mismas reglas de permiso que en show
        $user = Auth::user();
        $roleName = strtolower($user->roles->nombre ?? ($user->role->nombre ?? ''));

        if ($roleName === 'orientador') {
            // ok
        } else {
            if ($cita->solicitante_id === $user->id) {
                // ok
            } else {
                $esAcudienteDelEstudiante = optional($cita->estudianteReferido)->acudiente_id === $user->id;
                $esEstudianteReferido = optional($cita->estudianteReferido)->id === $user->id;

                if (! $esAcudienteDelEstudiante && ! $esEstudianteReferido) {
                    abort(403, 'No tienes permisos para ver esta revisión.');
                }
            }
        }

        $cita->load(['solicitante', 'orientador', 'estudianteReferido', 'children']);
        return view('citas.partials.revision', compact('cita'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Cita $cita)
    {
        // Solo el solicitante puede editar citas no programadas
        $user = Auth::user();
        if ($cita->estado !== 'solicitada' && $user->id !== $cita->solicitante_id) {
            return redirect()->route('citas.show', $cita)
                            ->with('error', 'No se puede editar una cita que ya ha sido programada.');
        }

        if ($user->id !== $cita->solicitante_id) {
            abort(403, 'No tienes permisos para editar esta cita.');
        }

        $orientadores = User::join('roles', 'users.roles_id', '=', 'roles.id')
                           ->where('roles.nombre', 'Orientador')
                           ->select('users.*')
                           ->orderBy('users.name')
                           ->get();
        
        $estudiantes = User::join('roles', 'users.roles_id', '=', 'roles.id')
                          ->where('roles.nombre', 'Estudiante')
                          ->select('users.*')
                          ->orderBy('users.name')
                          ->get();

        return view('citas.edit', compact('cita', 'orientadores', 'estudiantes'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Cita $cita)
    {
        // Verificar permisos: solo el solicitante puede actualizar solicitudes no programadas
        $user = Auth::user();
        if ($cita->estado !== 'solicitada' && $user->id !== $cita->solicitante_id) {
            return redirect()->route('citas.show', $cita)
                            ->with('error', 'No se puede editar una cita que ya ha sido programada.');
        }

        $validated = $request->validate([
            'estudiante_referido_id' => 'nullable|exists:users,id',
            'orientador_id' => 'nullable|exists:users,id',
            'tipo_cita' => 'required|in:orientacion,psicologica,disciplinaria,familiar,seguimiento,vocacional',
            'modalidad' => 'required|in:presencial,virtual,telefonica',
            'motivo' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'observaciones_previas' => 'nullable|string',
            'fecha_solicitada' => 'required|date|after_or_equal:today',
            'hora_solicitada' => 'required|date_format:H:i',
            'duracion_estimada' => 'required|integer|min:15|max:180',
            'prioridad' => 'required|in:baja,media,alta,urgente',
            'lugar_cita' => 'nullable|string|max:255',
            'link_virtual' => 'nullable|url',
            'instrucciones_adicionales' => 'nullable|string'
        ]);

        $cita->update($validated);

        return redirect()->route('citas.show', $cita)
                        ->with('success', 'Cita actualizada exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Cita $cita)
    {
        // Solo el solicitante puede eliminar su solicitud
        $user = Auth::user();
        if ($user->id !== $cita->solicitante_id) {
            abort(403, 'No tienes permisos para eliminar esta cita.');
        }

        if (!$cita->puedeSerCancelada()) {
            return redirect()->route('citas.show', $cita)
                            ->with('error', 'No se puede eliminar una cita en este estado.');
        }

        $cita->delete();

        return redirect()->route('citas.index')
                        ->with('success', 'Cita eliminada exitosamente.');
    }

    /**
     * Programar una cita (solo orientadores/administradores)
     */
    public function programar(Request $request, Cita $cita)
    {
        $user = Auth::user();
        $roleName = strtolower($user->roles->nombre ?? ($user->role->nombre ?? ''));
        if ($roleName !== 'orientador') {
            abort(403, 'No tienes permisos para programar citas.');
        }

        $validated = $request->validate([
            'fecha_asignada' => 'required|date|after_or_equal:today',
            'hora_asignada' => 'required|date_format:H:i',
            'orientador_id' => 'required|exists:users,id',
            'lugar_cita' => 'nullable|string|max:255',
            'link_virtual' => 'nullable|url',
            'instrucciones_adicionales' => 'nullable|string'
        ]);

        // Verificar disponibilidad
        $conflicto = Cita::where('orientador_id', $validated['orientador_id'])
                         ->where('fecha_asignada', $validated['fecha_asignada'])
                         ->where('hora_asignada', $validated['hora_asignada'])
                         ->where('id', '!=', $cita->id)
                         ->whereIn('estado', ['programada', 'confirmada', 'en_curso'])
                         ->exists();
        
        if ($conflicto) {
            return back()->withErrors(['orientador_id' => 'Ya existe una cita programada en esa fecha y hora.']);
        }

        $cita->programar($validated['fecha_asignada'], $validated['hora_asignada'], $validated['orientador_id']);
        
        // Actualizar campos adicionales
        $cita->update([
            'lugar_cita' => $validated['lugar_cita'],
            'link_virtual' => $validated['link_virtual'],
            'instrucciones_adicionales' => $validated['instrucciones_adicionales']
        ]);

        return redirect()->route('citas.show', $cita)
                        ->with('success', 'Cita programada exitosamente.');
    }

    /**
     * Confirmar asistencia a una cita
     */
    public function confirmar(Cita $cita)
    {
        if ($cita->estado !== 'programada') {
            return redirect()->route('citas.show', $cita)
                            ->with('error', 'Solo se pueden confirmar citas programadas.');
        }

        $cita->confirmar();

        return redirect()->route('citas.show', $cita)
                        ->with('success', 'Asistencia confirmada exitosamente.');
    }

    /**
     * Iniciar una cita
     */
    public function iniciar(Cita $cita)
    {
        $user = Auth::user();
        $roleName = strtolower($user->roles->nombre ?? ($user->role->nombre ?? ''));
        if ($roleName !== 'orientador') {
            abort(403, 'Solo los orientadores pueden iniciar la cita.');
        }

        if (!in_array($cita->estado, ['programada', 'confirmada'])) {
            return redirect()->route('citas.show', $cita)
                            ->with('error', 'Solo se pueden iniciar citas programadas o confirmadas.');
        }

        $cita->iniciar();

        return redirect()->route('citas.show', $cita)
                        ->with('success', 'Cita iniciada. Recuerda completar el resumen al finalizar.');
    }

    /**
     * Completar una cita con resumen
     */
    public function completar(Request $request, Cita $cita)
    {
        $user = Auth::user();
        $roleName = strtolower($user->roles->nombre ?? ($user->role->nombre ?? ''));
        if ($roleName !== 'orientador') {
            abort(403, 'Solo el orientador puede completar la cita.');
        }

        $validated = $request->validate([
            'resumen_cita' => 'required|string',
            'recomendaciones' => 'nullable|string',
            'plan_seguimiento' => 'nullable|string',
            'requiere_seguimiento' => 'boolean',
            'fecha_seguimiento' => 'nullable|date|after:today'
        ]);

        $cita->completar(
            $validated['resumen_cita'],
            $validated['recomendaciones'] ?? null,
            $validated['plan_seguimiento'] ?? null
        );

        // Registrar orientador si no está asignado
        if (empty($cita->orientador_id)) {
            $cita->orientador_id = $user->id;
            $cita->save();
        }

        if (!empty($validated['requiere_seguimiento']) && !empty($validated['fecha_seguimiento'])) {
            $cita->update([
                'requiere_seguimiento' => true,
                'fecha_seguimiento' => $validated['fecha_seguimiento'],
            ]);

            // Crear cita de seguimiento vinculada
            try {
                $seguimientoData = [
                    'parent_cita_id' => $cita->id,
                    'solicitante_id' => $cita->solicitante_id,
                    'estudiante_referido_id' => $cita->estudiante_referido_id,
                    'tipo_cita' => 'seguimiento',
                    'modalidad' => $cita->modalidad ?? 'presencial',
                    'motivo' => 'Seguimiento de la cita #' . $cita->id . ' - ' . substr($cita->motivo ?? '', 0, 120),
                    'descripcion' => $cita->descripcion,
                    'fecha_solicitada' => $validated['fecha_seguimiento'],
                    'hora_solicitada' => $validated['hora_seguimiento'] ?? null,
                    'fecha_asignada' => $validated['fecha_seguimiento'],
                    'hora_asignada' => $validated['hora_seguimiento'] ?? null,
                    'estado' => 'programada',
                    'duracion_estimada' => $cita->duracion_estimada ?? 30,
                    'orientador_id' => $user->id,
                    'prioridad' => $cita->prioridad ?? 'media'
                ];

                \App\Models\Cita::create($seguimientoData);
            } catch (\Throwable $e) {
                logger()->error('Error creando cita de seguimiento (CitasController): ' . $e->getMessage(), ['cita_id' => $cita->id]);
            }
        }

        return redirect()->route('citas.show', $cita)
                        ->with('success', 'Cita completada exitosamente.');
    }

    /**
     * Cancelar una cita
     */
    public function cancelar(Request $request, Cita $cita)
    {
        // Solo orientador puede registrar una cita como "no asistió" o cancelar desde la vista de gestión
        $user = Auth::user();
        $roleName = strtolower($user->roles->nombre ?? ($user->role->nombre ?? ''));
        if ($roleName !== 'orientador') {
            abort(403, 'No tienes permisos para cancelar esta cita.');
        }

        $validated = $request->validate([
            'motivo_cancelacion' => 'required|string|max:500'
        ]);

        if (!$cita->puedeSerCancelada()) {
            return redirect()->route('citas.show', $cita)
                            ->with('error', 'No se puede cancelar una cita en este estado.');
        }

        $cita->cancelar($validated['motivo_cancelacion'], Auth::id());

        return redirect()->route('citas.show', $cita)
                        ->with('success', 'Cita cancelada exitosamente.');
    }

    /**
     * Reprogramar una cita
     */
    public function reprogramar(Request $request, Cita $cita)
    {
        $validated = $request->validate([
            'fecha_asignada' => 'required|date|after_or_equal:today',
            'hora_asignada' => 'required|date_format:H:i'
        ]);

        if (!$cita->puedeSerReprogramada()) {
            return redirect()->route('citas.show', $cita)
                            ->with('error', 'No se puede reprogramar una cita en este estado.');
        }

        $cita->reprogramar($validated['fecha_asignada'], $validated['hora_asignada']);

        return redirect()->route('citas.show', $cita)
                        ->with('success', 'Cita reprogramada exitosamente.');
    }

    /**
     * Vista del calendario de citas
     */
    public function calendario()
    {
        $user = Auth::user();
        $query = Cita::with(['solicitante', 'orientador', 'estudianteReferido'])
                     ->whereNotNull('fecha_asignada')
                     ->whereIn('estado', ['programada', 'confirmada', 'en_curso']);

        // Filtrar por rol
        if ($user->roles->nombre === 'Orientador') {
            $query->where('orientador_id', $user->id);
        } elseif ($user->roles->nombre === 'Acudiente') {
            $query->where('solicitante_id', $user->id);
        }

        $citas = $query->get();

        return view('citas.calendario', compact('citas'));
    }

    /**
     * API para obtener citas del calendario
     */
    public function citasCalendario(Request $request)
    {
        $user = Auth::user();
        $query = Cita::with(['solicitante', 'orientador', 'estudianteReferido'])
                     ->whereNotNull('fecha_asignada');

        if ($request->filled('start')) {
            $query->where('fecha_asignada', '>=', $request->start);
        }

        if ($request->filled('end')) {
            $query->where('fecha_asignada', '<=', $request->end);
        }

        // Filtrar por rol
        if ($user->roles->nombre === 'Orientador') {
            $query->where('orientador_id', $user->id);
        } elseif ($user->roles->nombre === 'Acudiente') {
            $query->where('solicitante_id', $user->id);
        }

        $citas = $query->get();

        $eventos = $citas->map(function ($cita) {
            return [
                'id' => $cita->id,
                'title' => $cita->motivo . ' - ' . $cita->solicitante->name,
                'start' => $cita->fecha_asignada . 'T' . $cita->hora_asignada,
                'end' => $cita->fecha_hora_completa ? $cita->fecha_hora_completa->addMinutes($cita->duracion_estimada)->format('Y-m-d\TH:i:s') : null,
                'backgroundColor' => $this->getColorByEstado($cita->estado),
                'borderColor' => $this->getColorByPrioridad($cita->prioridad),
                'url' => route('citas.show', $cita->id)
            ];
        });

        return response()->json($eventos);
    }

    /**
     * Obtener color por estado
     */
    private function getColorByEstado($estado)
    {
        $colores = [
            'solicitada' => '#ffc107',
            'programada' => '#17a2b8',
            'confirmada' => '#28a745',
            'en_curso' => '#fd7e14',
            'completada' => '#6c757d',
            'cancelada' => '#dc3545',
            'reprogramada' => '#6f42c1'
        ];

        return $colores[$estado] ?? '#6c757d';
    }

    /**
     * Obtener color por prioridad
     */
    private function getColorByPrioridad($prioridad)
    {
        $colores = [
            'baja' => '#28a745',
            'media' => '#ffc107',
            'alta' => '#fd7e14',
            'urgente' => '#dc3545'
        ];

        return $colores[$prioridad] ?? '#6c757d';
    }
}