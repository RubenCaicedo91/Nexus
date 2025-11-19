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
        $user = Auth::user();
        $isCoordinator = $this->isCoordinadorAcademico($user);
        return view('orientacion.index', compact('isCoordinator'));
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

        $isCoordinator = $this->isCoordinadorAcademico($user);
        return view('orientacion.citas.index', compact('citas', 'students', 'orientadores', 'isCoordinator'));
    }

    public function crearCita()
    {
        $isCoordinator = $this->isCoordinadorAcademico(Auth::user());
        return view('orientacion.citas.create', compact('isCoordinator'));
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
    public function listarInformes(Request $request)
    {
        // Denegar acceso a Coordinador Académico: sólo puede usar el submódulo de citas
        if ($this->isCoordinadorAcademico(Auth::user())) {
            abort(403, 'Acceso restringido: el Coordinador Académico sólo puede gestionar citas en este módulo.');
        }
        // Construir consulta sobre citas completadas (quienes fueron atendidas)
        $query = Cita::with(['solicitante.role', 'orientador.role'])->where('estado', 'completada');

        // Filtros: solicitante (id), rol del solicitante y tipo de cita
        if ($request->filled('solicitante_id')) {
            $query->where('solicitante_id', $request->solicitante_id);
        }

        if ($request->filled('rol')) {
            $rol = trim($request->rol);
            $query->whereHas('solicitante.role', function ($q) use ($rol) {
                $q->where('nombre', $rol);
            });
        }

        if ($request->filled('tipo')) {
            $query->where('tipo_cita', $request->tipo);
        }

        $citas = $query->orderBy('fecha_solicitada', 'desc')->paginate(25)->appends($request->query());

        // Lista de roles disponibles, solicitantes y tipos para los select de filtros
        $roles = [];
        try {
            $roles = \App\Models\RolesModel::orderBy('nombre')->pluck('nombre')->toArray();
        } catch (\Throwable $e) {
            $roles = [];
        }

        $tipos = Cita::TIPOS_CITA;

        // Lista de usuarios que han solicitado citas (únicos)
        try {
            $solicitanteIds = Cita::whereNotNull('solicitante_id')->pluck('solicitante_id')->unique()->toArray();
            $solicitantes = \App\Models\User::whereIn('id', $solicitanteIds)->orderBy('name')->get();
        } catch (\Throwable $e) {
            $solicitantes = collect();
        }

            // Comparación entre los tipos definidos en el modelo y los tipos realmente usados en la tabla
            try {
                $presentTipos = Cita::select('tipo_cita')->distinct()->pluck('tipo_cita')->filter()->values()->toArray();
            } catch (\Throwable $e) {
                $presentTipos = [];
            }

            $definedTipos = array_keys($tipos);
            $tiposNoDefinidos = array_diff($presentTipos, $definedTipos); // usados en DB pero no definidos en TIPOS_CITA
            $tiposNoUsados = array_diff($definedTipos, $presentTipos); // definidos pero sin registros

        return view('orientacion.informes.index', compact('citas', 'roles', 'tipos', 'solicitantes', 'presentTipos', 'definedTipos', 'tiposNoDefinidos', 'tiposNoUsados'));
    }

    /**
     * Exportar los informes (citas completadas) aplicando los mismos filtros a PDF
     */
    public function exportarInformesPdf(Request $request)
    {
        if ($this->isCoordinadorAcademico(Auth::user())) {
            abort(403, 'Acceso restringido: el Coordinador Académico sólo puede gestionar citas en este módulo.');
        }
        // Reusar la misma lógica de filtros de listarInformes
        $query = Cita::with(['solicitante.role', 'orientador.role'])->where('estado', 'completada');

        if ($request->filled('solicitante_id')) {
            $query->where('solicitante_id', $request->solicitante_id);
        }

        if ($request->filled('rol')) {
            $rol = trim($request->rol);
            $query->whereHas('solicitante.role', function ($q) use ($rol) {
                $q->where('nombre', $rol);
            });
        }

        if ($request->filled('tipo')) {
            $query->where('tipo_cita', $request->tipo);
        }

        $citas = $query->orderBy('fecha_solicitada', 'desc')->get();
        $tipos = Cita::TIPOS_CITA;

        // Si la dependencia de DomPDF no está instalada, devolver mensaje claro
        if (! class_exists('Barryvdh\\DomPDF\\Facade\\Pdf')) {
            return redirect()->route('orientacion.informes')
                             ->with('error', 'No está instalada la librería para generar PDF. Ejecuta: composer require barryvdh/laravel-dompdf');
        }

        try {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('orientacion.informes.pdf', compact('citas', 'tipos', 'request'))
                      ->setPaper('a4', 'landscape');

            $filename = 'informes_citas_' . now()->format('Ymd_His') . '.pdf';
            return $pdf->stream($filename);
        } catch (\Throwable $e) {
            return redirect()->route('orientacion.informes')
                             ->with('error', 'Error generando PDF: ' . $e->getMessage());
        }
    }

    /**
     * Exportar los informes (citas completadas) aplicando los mismos filtros a CSV compatible con Excel
     */
    public function exportarInformesExcel(Request $request)
    {
        if ($this->isCoordinadorAcademico(Auth::user())) {
            abort(403, 'Acceso restringido: el Coordinador Académico sólo puede gestionar citas en este módulo.');
        }
        $query = Cita::with(['solicitante.role', 'orientador.role'])->where('estado', 'completada');

        if ($request->filled('solicitante_id')) {
            $query->where('solicitante_id', $request->solicitante_id);
        }

        if ($request->filled('rol')) {
            $rol = trim($request->rol);
            $query->whereHas('solicitante.role', function ($q) use ($rol) {
                $q->where('nombre', $rol);
            });
        }

        if ($request->filled('tipo')) {
            $query->where('tipo_cita', $request->tipo);
        }

        $citas = $query->orderBy('fecha_solicitada', 'desc')->get();
        $tipos = Cita::TIPOS_CITA;

        $filename = 'informes_citas_' . now()->format('Ymd_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $columns = ['ID','Solicitante','Rol','Tipo','Fecha solicitud','Atendido por','Motivo/Resumen'];

        $callback = function() use ($citas, $columns, $tipos) {
            $out = fopen('php://output', 'w');
            // BOM for UTF-8 so Excel recognizes encoding
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, $columns);

            foreach ($citas as $cita) {
                $row = [
                    $cita->id,
                    optional($cita->solicitante)->name ?? 'N/A',
                    optional(optional($cita->solicitante)->role)->nombre ?? 'N/A',
                    $tipos[$cita->tipo_cita] ?? $cita->tipo_cita,
                    $cita->fecha_solicitada ? \Carbon\Carbon::parse($cita->fecha_solicitada)->format('d/m/Y') . ' ' . ($cita->hora_solicitada ?? '') : 'Sin fecha',
                    optional($cita->orientador)->name ?? 'Sin asignar',
                    strip_tags($cita->resumen_cita ?? $cita->motivo ?? ''),
                ];
                fputcsv($out, $row);
            }

            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    // Nota: la vista de creación de informes fue eliminada según solicitud.

    public function guardarInforme(Request $request)
    {
        if ($this->isCoordinadorAcademico(Auth::user())) {
            abort(403, 'Acceso restringido: el Coordinador Académico sólo puede gestionar citas en este módulo.');
        }
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
    public function listarSeguimientos(Request $request)
    {
        // Denegar acceso a Coordinador Académico: sólo puede usar Citas
        if ($this->isCoordinadorAcademico(Auth::user())) {
            abort(403, 'Acceso restringido: el Coordinador Académico sólo puede gestionar citas en este módulo.');
        }
        // Construir la consulta base incluyendo relaciones útiles
        $query = Seguimiento::with(['estudiante', 'responsable', 'cita'])->orderBy('fecha', 'desc');

        // Filtro por estudiante: preferimos recibir `usuario_id` (id del usuario) para evitar ambigüedades
        if ($request->filled('usuario_id')) {
            $query->where('estudiante_id', $request->usuario_id);
        }

        // Filtro por tipo de seguimiento
        if ($request->filled('tipo')) {
            $query->where('tipo_seguimiento', $request->tipo);
        }

        // Filtro por estado del seguimiento: 'atendio' => atendido/completado, 'no_atendio' => no completado
        if ($request->filled('estado')) {
            $estadoFiltro = $request->estado;

            // Preferir columna propia 'estado_seguimiento' si existe
            if (\Illuminate\Support\Facades\Schema::hasColumn('seguimientos', 'estado_seguimiento')) {
                if ($estadoFiltro === 'atendio') {
                    $query->where('estado_seguimiento', 'completado');
                } elseif ($estadoFiltro === 'no_atendio') {
                    $query->where(function($q) {
                        $q->whereNull('estado_seguimiento')->orWhere('estado_seguimiento', '!=', 'completado');
                    });
                }

            // Si no existe esa columna, intentar filtrar por la cita relacionada (si existe la FK y la columna estado en citas)
            } elseif (\Illuminate\Support\Facades\Schema::hasColumn('seguimientos', 'cita_id') && \Illuminate\Support\Facades\Schema::hasColumn('citas', 'estado')) {
                if ($estadoFiltro === 'atendio') {
                    $query->whereHas('cita', function($q) {
                        $q->where('estado', 'completada');
                    });
                } elseif ($estadoFiltro === 'no_atendio') {
                    $query->whereDoesntHave('cita', function($q) {
                        $q->where('estado', 'completada');
                    });
                }

            // Si ninguna columna disponible, ignorar el filtro (no romper la consulta)
            } else {
                // no se puede aplicar filtro por estado con el esquema actual
            }
        }

        // Obtener resultados desde la tabla de seguimientos
        $seguimientos = $query->get();

        // Además incluir las citas marcadas como tipo 'seguimiento' para mostrar casos creados desde citas
        $citaSeguimientos = collect();
        try {
            $citaQuery = Cita::with(['estudianteReferido', 'solicitante', 'orientador'])->where('tipo_cita', 'seguimiento');

            // Si se filtró por estudiante_id, aplicarlo también a las citas
            if ($request->filled('usuario_id')) {
                $citaQuery->where(function($q) use ($request) {
                    $q->where('estudiante_referido_id', $request->usuario_id)
                      ->orWhere('solicitante_id', $request->usuario_id);
                });
            }

            // Si se filtró por estado (atendio/no_atendio) y la tabla citas tiene la columna, aplicarlo
            if ($request->filled('estado') && \Illuminate\Support\Facades\Schema::hasColumn('citas', 'estado')) {
                if ($request->estado === 'atendio') {
                    $citaQuery->where('estado', 'completada');
                } elseif ($request->estado === 'no_atendio') {
                    $citaQuery->where('estado', '!=', 'completada');
                }
            }

            $citaSeguimientos = $citaQuery->get()->map(function($c) {
                // Añadir propiedades compatibles para que la vista las muestre como seguimientos
                $c->tipo_seguimiento = 'seguimiento';
                $c->titulo = $c->motivo ?? ('Cita seguimiento #' . $c->id);
                $c->fecha = $c->fecha_asignada ?? $c->fecha_solicitada ?? null;
                $c->estudiante = $c->estudianteReferido ?? $c->solicitante;
                $c->estudiante_id = $c->estudiante_referido_id ?? $c->solicitante_id ?? null;
                $c->is_cita = true;
                return $c;
            });
        } catch (\Throwable $e) {
            $citaSeguimientos = collect();
        }

        // Fusionar colecciones y ordenar por fecha
        $todos = $seguimientos->concat($citaSeguimientos)->sortByDesc(function($item) {
            return $item->fecha ?? ($item->created_at ?? null);
        })->values();

        // Agrupar por estudiante para mostrar usuarios que tienen varios seguimientos
        $seguimientosGrouped = $todos->groupBy(function ($s) {
            return $s->estudiante_id ?: 'sin_estudiante';
        });

        // Obtener lista de usuarios que tienen seguimientos (sin aplicar filtros) para poblar el select
        $usuarios = collect();
        try {
            $allUsuarioIds = Seguimiento::pluck('estudiante_id')->unique()->filter()->values()->toArray();
            if (!empty($allUsuarioIds)) {
                $usuarios = \App\Models\User::whereIn('id', $allUsuarioIds)->orderBy('name')->get();
            }
        } catch (\Throwable $e) {
            $usuarios = collect();
        }

        // Usar los mismos tipos que las citas (Orientación Académica/Psicológica/Vocacional/Otro)
        $tipos = Cita::TIPOS_CITA;
        // Solo dos estados para el filtro según requerimiento
        $estados = [
            'atendio' => 'Atendido',
            'no_atendio' => 'No atendido'
        ];

        return view('orientacion.seguimientos.index', compact('seguimientos', 'seguimientosGrouped', 'usuarios', 'tipos', 'estados', 'request'));
    }

    public function guardarSeguimiento(Request $request)
    {
        if ($this->isCoordinadorAcademico(Auth::user())) {
            abort(403, 'Acceso restringido: el Coordinador Académico sólo puede gestionar citas en este módulo.');
        }
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

    /**
     * Determina si el usuario tiene rol "Coordinador Académico".
     * Normaliza tildes y posibles variantes ortográficas.
     */
    private function isCoordinadorAcademico($user = null)
    {
        if (! $user) return false;

        $roleName = null;
        if (isset($user->roles) && is_object($user->roles) && isset($user->roles->nombre)) {
            $roleName = $user->roles->nombre;
        } elseif (method_exists($user, 'role') && optional($user->role)->nombre) {
            $roleName = optional($user->role)->nombre;
        } elseif (isset($user->roles_id)) {
            try {
                $r = RolesModel::find($user->roles_id);
                $roleName = $r ? $r->nombre : null;
            } catch (\Throwable $e) {
                $roleName = null;
            }
        }

        if (! $roleName) return false;

        // Normalizar: quitar tildes, pasar a minúsculas y eliminar caracteres no alfabéticos excepto espacios
        $normalized = strtolower($roleName);
        $normalized = str_replace(['á','é','í','ó','ú','Á','É','Í','Ó','Ú','ñ','Ñ'], ['a','e','i','o','u','A','E','I','O','U','n','N'], $normalized);
        $normalized = preg_replace('/[^a-z0-9\s]/u', '', $normalized);
        $normalized = trim($normalized);

        $valids = [
            'coordinador academico',
            'coordinador academico', // duplicate to be explicit
            'coordinador academico',
            'cordinador academico'
        ];

        return in_array($normalized, $valids, true);
    }
}
