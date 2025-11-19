<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cita;
use App\Models\Informe;
use App\Models\Seguimiento;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use App\Models\RolesModel;

class GestionOrientacionController extends Controller
{
    // Vista principal del módulo
    public function index()
    {
        return view('orientacion.index');
    }

    // ---------------- Citas ----------------
    public function listarCitas()
    {
        $user = Auth::user();
        $students = null;

        // Determinar nombre del rol (manejar posibles diferencias en relación 'role' vs 'roles')
        $roleName = null;
        if ($user) {
            if (isset($user->roles) && is_object($user->roles) && isset($user->roles->nombre)) {
                $roleName = $user->roles->nombre;
            } elseif (method_exists($user, 'role') && optional($user->role)->nombre) {
                $roleName = optional($user->role)->nombre;
            } elseif (isset($user->roles_id)) {
                $role = RolesModel::find($user->roles_id);
                $roleName = $role ? $role->nombre : null;
            }

            if (strtolower(trim((string)$roleName)) === 'acudiente') {
                $students = $user->acudientes()->orderBy('name')->get();
            }
        }

        // Solo el rol Orientador puede ver todas las solicitudes; los demás ven solo sus propias solicitudes
        // Determine which relations to eager load depending on DB schema
        $with = ['orientador', 'solicitante'];
        if (Schema::hasColumn('citas', 'parent_cita_id')) {
            $with[] = 'children';
            $with[] = 'parent';
        }

        if (strtolower(trim((string)$roleName)) === 'orientador') {
            $citas = Cita::with($with)
                        ->orderBy('created_at', 'desc')
                        ->get();
        } else {
            // Para otros usuarios: si es Acudiente mostramos citas solicitadas por el acudiente y las de sus estudiantes
            $query = Cita::with($with)->orderBy('created_at', 'desc');
            if (strtolower(trim((string)$roleName)) === 'acudiente') {
                $studentIds = $user->acudientes()->pluck('id')->toArray();
                $query->where(function($q) use ($user, $studentIds) {
                    $q->where('solicitante_id', $user->id)
                      ->orWhereIn('estudiante_referido_id', $studentIds ?: [0]);
                });
            } else {
                $query->where('solicitante_id', $user->id);
            }

            $citas = $query->get();
        }

        // Cargar lista de orientadores para acciones (asignar/cambiar)
        $orientadores = [];
        try {
            $rol = RolesModel::where('nombre', 'Orientador')->first();
            if ($rol) {
                $orientadores = \App\Models\User::where('roles_id', $rol->id)->orderBy('name')->get();
            }
        } catch (\Throwable $e) {
            $orientadores = [];
        }

        return view('orientacion.citas.index', compact('citas', 'students', 'orientadores'));
    }

    public function crearCita()
    {
        return view('orientacion.citas.create');
    }

    public function guardarCita(Request $request)
    {
        $request->validate([
            'fecha' => 'required|date_format:Y-m-d\\TH:i',
            'estudiante_id' => 'required|integer',
            'tipo_cita' => 'required|in:orientacion,psicologica,vocacional,otro',
            'motivo' => 'required|string|max:255',
            'descripcion' => 'nullable|string'
        ]);

        // Parsear fecha y hora
        try {
            $dt = Carbon::parse($request->fecha);
        } catch (\Exception $e) {
            return back()->withErrors(['fecha' => 'Formato de fecha/hora inválido.'])->withInput();
        }

        // Validar día (lunes-viernes)
        if (!$dt->isWeekday()) {
            return back()->withErrors(['fecha' => 'Las citas solo se pueden solicitar de lunes a viernes.'])->withInput();
        }

        // Validar horario permitido:
        // - Mañana: 08:00 (inclusive) hasta 11:59
        // - Tarde: 13:00 hasta 16:00 (inclusive)
        $minutes = $dt->hour * 60 + $dt->minute;
        $morningStart = 8 * 60;   // 08:00
        $morningEnd = 12 * 60;    // 12:00 (exclusive)
        $afternoonStart = 13 * 60; // 13:00
        $afternoonEnd = 16 * 60;   // 16:00 (inclusive)

        $isMorning = ($minutes >= $morningStart && $minutes < $morningEnd);
        $isAfternoon = ($minutes >= $afternoonStart && $minutes <= $afternoonEnd);

        if (!($isMorning || $isAfternoon)) {
            return back()->withErrors(['fecha' => 'Las citas solo pueden solicitarse entre 08:00–11:59 y 13:00–16:00.'])->withInput();
        }

        $date = $dt->toDateString();
        $time = $dt->format('H:i');

        // Verificar disponibilidad (evitar duplicados en la misma fecha y hora)
        $conflicto = Cita::where(function ($q) use ($date, $time) {
            $q->where(function ($q2) use ($date, $time) {
                $q2->where('fecha_solicitada', $date)->where('hora_solicitada', $time);
            })->orWhere(function ($q3) use ($date, $time) {
                $q3->where('fecha_asignada', $date)->where('hora_asignada', $time);
            });
        })->whereIn('estado', ['solicitada', 'programada', 'confirmada'])->exists();

        if ($conflicto) {
            return back()->withErrors(['fecha' => 'Ya existe una cita solicitada o programada en esa fecha y hora.'])->withInput();
        }

        // Si el usuario es Acudiente, validar que el estudiante pertenece a él
        $user = Auth::user();
        $roleName = null;
        if ($user) {
            if (isset($user->roles) && is_object($user->roles) && isset($user->roles->nombre)) {
                $roleName = $user->roles->nombre;
            } elseif (method_exists($user, 'role') && optional($user->role)->nombre) {
                $roleName = optional($user->role)->nombre;
            } elseif (isset($user->roles_id)) {
                $role = RolesModel::find($user->roles_id);
                $roleName = $role ? $role->nombre : null;
            }
        }

        if (strtolower(trim((string)$roleName)) === 'acudiente') {
            // Verificar que el estudiante enviado pertenece al acudiente
            $estudiante = $user->acudientes()->where('id', $request->estudiante_id)->first();
            if (! $estudiante) {
                return back()->withErrors(['estudiante_id' => 'El estudiante seleccionado no está asociado a tu cuenta.'])->withInput();
            }
        }

        // Si el solicitante es Acudiente y seleccionó un estudiante, almacenar la cita a nombre del estudiante
        $solicitanteId = Auth::id();
        if (strtolower(trim((string)$roleName)) === 'acudiente' && !empty($request->estudiante_id)) {
            // asignar la cita al estudiante referido para que aparezca con su nombre
            $solicitanteId = $request->estudiante_id;
        }

        // Crear la cita usando los campos del modelo Cita
        Cita::create([
            'solicitante_id' => $solicitanteId,
            'estudiante_referido_id' => $request->estudiante_id,
            'fecha_solicitada' => $date,
            'hora_solicitada' => $time,
            'estado' => 'solicitada',
            'tipo_cita' => $request->tipo_cita,
            'motivo' => $request->motivo,
            'descripcion' => $request->descripcion ?? null,
            'duracion_estimada' => 30
        ]);

        return redirect()->route('orientacion.citas')->with('success', 'Cita solicitada correctamente.');
    }

    public function cambiarEstadoCita($id, Request $request)
    {
        $request->validate([
            // Usar los estados definidos en el modelo Cita
            'estado' => 'required|in:programada,completada,cancelada,reprogramada,confirmada'
        ]);

        $cita = Cita::findOrFail($id);

        // No permitir cambios en citas que ya están finalizadas
        if ($cita->esCompletada() || $cita->esCancelada()) {
            return back()->with('error', 'No se puede cambiar el estado de una cita que ya está finalizada.');
        }
        // Permitir cambio de estado sólo a orientadores
        $user = Auth::user();
        $roleName = null;
        if ($user) {
            if (isset($user->roles) && is_object($user->roles) && isset($user->roles->nombre)) {
                $roleName = $user->roles->nombre;
            } elseif (method_exists($user, 'role') && optional($user->role)->nombre) {
                $roleName = optional($user->role)->nombre;
            } elseif (isset($user->roles_id)) {
                $role = RolesModel::find($user->roles_id);
                $roleName = $role ? $role->nombre : null;
            }
        }

        if (strtolower(trim((string)$roleName)) !== 'orientador') {
            abort(403, 'No tienes permisos para cambiar el estado de la cita.');
        }

        $cita->estado = $request->estado;
        $cita->save();

        return back()->with('success', 'Estado de la cita actualizado');
    }

    /**
     * Completar una cita (registro de atención) desde el módulo de orientación.
     * Sólo orientadores pueden hacerlo.
     */
    public function completarCita($id, Request $request)
    {
        $user = Auth::user();
        $roleName = null;
        if ($user) {
            if (isset($user->roles) && is_object($user->roles) && isset($user->roles->nombre)) {
                $roleName = $user->roles->nombre;
            } elseif (method_exists($user, 'role') && optional($user->role)->nombre) {
                $roleName = optional($user->role)->nombre;
            }
        }

        if (strtolower(trim((string)$roleName)) !== 'orientador') {
            abort(403, 'No tienes permisos para completar esta cita.');
        }

        $validated = $request->validate([
            'resumen_cita' => 'required|string',
            'recomendaciones' => 'nullable|string',
            'plan_seguimiento' => 'nullable|string',
            'requiere_seguimiento' => 'nullable|boolean',
            'fecha_seguimiento' => 'nullable|date|after:today',
            'hora_seguimiento' => 'nullable|date_format:H:i'
        ]);

        $cita = Cita::findOrFail($id);

        // Evitar completar una cita que ya está finalizada
        if ($cita->esCompletada() || $cita->esCancelada()) {
            return back()->with('error', 'No se puede completar una cita que ya está finalizada.');
        }

        // Usar el método del modelo para completar
        $cita->completar(
            $validated['resumen_cita'],
            $validated['recomendaciones'] ?? null,
            $validated['plan_seguimiento'] ?? null
        );

        if (!empty($validated['requiere_seguimiento']) && !empty($validated['fecha_seguimiento'])) {
            $data = [
                'requiere_seguimiento' => true,
                'fecha_seguimiento' => $validated['fecha_seguimiento']
            ];
            if (!empty($validated['hora_seguimiento'])) {
                $data['hora_seguimiento'] = $validated['hora_seguimiento'];
            }
            $cita->update($data);
        }

        // Si se solicitó seguimiento, crear una nueva cita tipo 'seguimiento' vinculada
        if (!empty($validated['requiere_seguimiento']) && !empty($validated['fecha_seguimiento'])) {
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
                // No romper el flujo principal si falla la creación del seguimiento, registrar el error
                logger()->error('Error creando cita de seguimiento: ' . $e->getMessage(), ['cita_id' => $cita->id]);
            }
        }

        return back()->with('success', 'Resumen registrado y cita marcada como completada.');
    }

    /**
     * Cancelar una cita desde el módulo de orientación (registro de no asistencia o motivo).
     * Sólo orientadores pueden hacerlo.
     */
    public function cancelarCita($id, Request $request)
    {
        $user = Auth::user();
        $roleName = null;
        if ($user) {
            if (isset($user->roles) && is_object($user->roles) && isset($user->roles->nombre)) {
                $roleName = $user->roles->nombre;
            } elseif (method_exists($user, 'role') && optional($user->role)->nombre) {
                $roleName = optional($user->role)->nombre;
            }
        }

        if (strtolower(trim((string)$roleName)) !== 'orientador') {
            abort(403, 'No tienes permisos para cancelar esta cita.');
        }

        $validated = $request->validate([
            'motivo_cancelacion' => 'required|string|max:500'
        ]);

        $cita = Cita::findOrFail($id);

        // Evitar cancelar si ya está finalizada (completada o cancelada)
        if ($cita->esCompletada() || $cita->esCancelada()) {
            return back()->with('error', 'No se puede cancelar una cita que ya está finalizada.');
        }

        if (!$cita->puedeSerCancelada()) {
            return back()->with('error', 'No se puede cancelar una cita en este estado.');
        }

        $cita->cancelar($validated['motivo_cancelacion'], $user->id);

        return back()->with('success', 'Cita cancelada correctamente.');
    }

    /**
     * Asignar o cambiar el orientador responsable de una cita.
     * Solo usuarios con rol 'orientador' pueden reasignar aquí.
     */
    public function asignarOrientador($id, Request $request)
    {
        $user = Auth::user();
        $roleName = null;
        if ($user) {
            if (isset($user->roles) && is_object($user->roles) && isset($user->roles->nombre)) {
                $roleName = $user->roles->nombre;
            } elseif (method_exists($user, 'role') && optional($user->role)->nombre) {
                $roleName = optional($user->role)->nombre;
            }
        }

        if (strtolower(trim((string)$roleName)) !== 'orientador') {
            abort(403, 'No tienes permisos para asignar el orientador de la cita.');
        }

        $validated = $request->validate([
            'orientador_id' => 'required|integer|exists:users,id'
        ]);

        $cita = Cita::findOrFail($id);

        // Evitar reasignar si la cita está finalizada
        if ($cita->esCompletada() || $cita->esCancelada()) {
            return back()->with('error', 'No se puede cambiar el orientador de una cita finalizada.');
        }

        // Verificar que el usuario seleccionado efectivamente tenga rol Orientador
        $target = \App\Models\User::find($validated['orientador_id']);
        $isOrientadorTarget = false;
        if ($target) {
            $r = null;
            if (isset($target->roles) && is_object($target->roles) && isset($target->roles->nombre)) {
                $r = $target->roles->nombre;
            } elseif (method_exists($target, 'role') && optional($target->role)->nombre) {
                $r = optional($target->role)->nombre;
            }
            $isOrientadorTarget = strtolower(trim((string)$r)) === 'orientador';
        }

        if (! $isOrientadorTarget) {
            return back()->with('error', 'El usuario seleccionado no es un orientador válido.');
        }

        $cita->orientador_id = $validated['orientador_id'];
        $cita->save();

        return back()->with('success', 'Orientador asignado/cambiado correctamente.');
    }

    // ---------------- Informes ----------------
    public function listarInformes()
    {
        $informes = Informe::with('cita')->get();
        return view('orientacion.informes.index', compact('informes'));
    }

    public function crearInforme()
    {
        $citas = Cita::where('estado', 'atendida')->get();
        return view('orientacion.informes.create', compact('citas'));
    }

    public function guardarInforme(Request $request)
    {
        $request->validate([
            'cita_id' => 'required|exists:citas,id',
            'descripcion' => 'required|string'
        ]);

        Informe::create([
            'cita_id' => $request->cita_id,
            'descripcion' => $request->descripcion
        ]);

        return redirect()->route('orientacion.informes')->with('success', 'Informe generado correctamente');
    }

    // ---------------- Seguimientos ----------------
    public function listarSeguimientos()
    {
        $seguimientos = Seguimiento::all();
        return view('orientacion.seguimientos.index', compact('seguimientos'));
    }

    public function crearSeguimiento()
    {
        return view('orientacion.seguimientos.create');
    }

    public function guardarSeguimiento(Request $request)
    {
        $request->validate([
            'estudiante_id' => 'required|integer',
            'observaciones' => 'required|string'
        ]);

        Seguimiento::create([
            'estudiante_id' => $request->estudiante_id,
            'observaciones' => $request->observaciones,
            'fecha' => now()
        ]);

        return redirect()->route('orientacion.seguimientos')->with('success', 'Seguimiento registrado correctamente');
    }
}
