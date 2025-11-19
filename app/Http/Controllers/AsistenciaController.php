<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\Curso;
use App\Models\Matricula;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Dompdf\Dompdf;
use Dompdf\Options;

class AsistenciaController extends Controller
{
    /**
     * Devuelve información sobre si el usuario actual es Estudiante y su curso activo.
     * @return array [isEstudiante(bool), studentCourseId|null, studentCourseNombre|null]
     */
    protected function getStudentCourseInfo()
    {
        $user = Auth::user();
        $isEstudiante = false;
        $studentCourseId = null;
        $studentCourseNombre = null;

        if (Auth::check() && optional($user->role)->nombre && mb_stripos(optional($user->role)->nombre, 'estudiante') !== false) {
            $isEstudiante = true;
            try {
                $lastMat = Matricula::where('user_id', $user->id)->where('estado', 'activo')->orderByDesc('id')->first();
                if ($lastMat) {
                    $studentCourseId = $lastMat->curso_id;
                    $cursoModel = Curso::find($studentCourseId);
                    $studentCourseNombre = $cursoModel ? $cursoModel->nombre : null;
                }
            } catch (\Throwable $e) {
                // no interrumpir, devolvemos nulls
            }
        }

        return [$isEstudiante, $studentCourseId, $studentCourseNombre];
    }
    public function index()
    {
        // filtros: fecha, curso_id, materia_id
        $query = Asistencia::with(['curso', 'estudiante', 'materia'])->orderBy('fecha', 'desc');

        $fecha = request()->query('fecha');
        $cursoId = request()->query('curso_id');
        $materiaId = request()->query('materia_id');

        // Si el usuario es Estudiante, forzamos el curso a su grupo y no permitimos ver otros cursos
        list($isEstudiante, $studentCourseId, $studentCourseNombre) = $this->getStudentCourseInfo();
        if ($isEstudiante) {
            // sobrescribimos cursoId para evitar que vea otros cursos
            $cursoId = $studentCourseId;
        }

        if ($fecha) {
            $query->whereDate('fecha', $fecha);
        }
        if ($cursoId) {
            $query->where('curso_id', $cursoId);
        }
        if ($materiaId !== null && $materiaId !== '') {
            $query->where('materia_id', $materiaId);
        }

        // Si es estudiante, además de limitar por curso, solo ver sus propios registros
        if ($isEstudiante) {
            try {
                $query->where('estudiante_id', Auth::id());
            } catch (\Throwable $e) {
                // no interrumpir si hay un problema con Auth
            }
        }

        $asistencias = $query->paginate(25)->appends(request()->query());

        // Cursos mostrados: si es estudiante, sólo su curso; si no, todos
        $cursos = $isEstudiante && $studentCourseId ? Curso::where('id', $studentCourseId)->orderBy('nombre')->get() : Curso::orderBy('nombre')->get();
        $materias = [];
        if ($cursoId) {
            $materias = \App\Models\Materia::where('curso_id', $cursoId)->orderBy('nombre')->get();
        }

        // Calcular estadísticas por cada registro (fila) que aparece en la página
        $rowStats = [];
        foreach ($asistencias->items() as $a) {
            $keyFecha = $a->fecha ? \Carbon\Carbon::parse($a->fecha)->format('Y-m-d') : null;
            $cursoIdRow = $a->curso_id;
            $materiaIdRow = $a->materia_id;

            // Normalizar materia_id: tratar cadena vacía como null para evitar
            // consultas WHERE 'materia_id' = '' que devuelven cero.
            if ($materiaIdRow === '') {
                $materiaIdRow = null;
            }

            $total = \App\Models\Matricula::where('curso_id', $cursoIdRow)->count();

            $asq = Asistencia::where('curso_id', $cursoIdRow)->whereDate('fecha', $keyFecha);
            if ($materiaIdRow === null) {
                $asq->whereNull('materia_id');
            } else {
                $asq->where('materia_id', $materiaIdRow);
            }

            $present = (clone $asq)->where('presente', 1)->count();
            $absent = (clone $asq)->where('presente', 0)->count();
            $excuse = (clone $asq)->whereNotNull('observacion')->where('observacion', '<>', '')->count();

            $rowStats[$a->id] = [
                'total' => $total,
                'present' => $present,
                'absent' => $absent,
                'excuse' => $excuse,
            ];
        }

        return view('gestion-academica.asistencias.index', compact('asistencias', 'cursos', 'materias', 'fecha', 'cursoId', 'materiaId', 'rowStats', 'isEstudiante', 'studentCourseId', 'studentCourseNombre'));
    }

    /**
     * Exportar asistencias filtradas a CSV.
     */
    public function export()
    {
        list($isEstudiante) = $this->getStudentCourseInfo();
        if ($isEstudiante) {
            abort(403, 'Estudiantes no pueden exportar asistencias');
        }

        $query = Asistencia::with(['curso', 'estudiante', 'materia'])->orderBy('fecha', 'desc');

        $fecha = request()->query('fecha');
        $cursoId = request()->query('curso_id');
        $materiaId = request()->query('materia_id');

        if ($fecha) $query->whereDate('fecha', $fecha);
        if ($cursoId) $query->where('curso_id', $cursoId);
        if ($materiaId) $query->where('materia_id', $materiaId);

        $asistencias = $query->get();

        // Calcular contadores agrupados por combinacion fecha+curso+materia
        $groupStats = [];
        $groups = Asistencia::selectRaw('fecha, curso_id, materia_id')
            ->when($fecha, function($q) use ($fecha){ $q->whereDate('fecha', $fecha); })
            ->when($cursoId, function($q) use ($cursoId){ $q->where('curso_id', $cursoId); })
            ->when($materiaId !== null && $materiaId !== '', function($q) use ($materiaId){ $q->where('materia_id', $materiaId); })
            ->groupBy('fecha', 'curso_id', 'materia_id')
            ->get();

        foreach ($groups as $g) {
            $f = $g->fecha ? \Carbon\Carbon::parse($g->fecha)->format('Y-m-d') : null;
            $cId = $g->curso_id;
            $mId = $g->materia_id;
            if ($mId === '') {
                $mId = null;
            }
            $key = md5($f . '_' . $cId . '_' . ($mId ?? 'NULL'));

            $total = \App\Models\Matricula::where('curso_id', $cId)->count();
            $asq = Asistencia::where('curso_id', $cId)->whereDate('fecha', $f);
            if ($mId === null) $asq->whereNull('materia_id'); else $asq->where('materia_id', $mId);
            $present = (clone $asq)->where('presente', 1)->count();
            $absent = (clone $asq)->where('presente', 0)->count();
            $excuse = (clone $asq)->whereNotNull('observacion')->where('observacion', '<>', '')->count();

            $cursoNombre = optional(\App\Models\Curso::find($cId))->nombre;
            $materiaNombre = $mId ? optional(\App\Models\Materia::find($mId))->nombre : null;

            $groupStats[$key] = [
                'fecha' => $f,
                'curso_id' => $cId,
                'curso_nombre' => $cursoNombre,
                'materia_id' => $mId,
                'materia_nombre' => $materiaNombre,
                'total' => $total,
                'present' => $present,
                'absent' => $absent,
                'excuse' => $excuse,
            ];
        }

        // Generar PDF usando Dompdf
        $html = view('gestion-academica.asistencias.export_pdf', compact('asistencias', 'groupStats'))->render();

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $filename = 'asistencias_export_'.date('Ymd_His').'.pdf';
        $output = $dompdf->output();
        return response($output, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    public function create(Request $request)
    {
        list($isEstudiante) = $this->getStudentCourseInfo();
        if ($isEstudiante) {
            abort(403, 'Estudiantes no pueden crear asistencias');
        }
        // Si se pasa curso_id en query, mostramos estudiantes matriculados en ese curso
        $cursos = Curso::orderBy('nombre')->get();
        $selectedCursoId = $request->query('curso_id');
        $selectedEstudianteId = $request->query('estudiante_id');

        $estudiantes = null;
        $matriculas = null;
        if ($selectedCursoId) {
            $curso = Curso::find($selectedCursoId);
            if (!$curso) {
                abort(404);
            }

            // permiso: solo docentes asignados o roles admin/coordinador
            if (!$this->canManageAttendance($selectedCursoId)) {
                abort(403, 'No autorizado para registrar asistencias en este curso');
            }

            $matriculas = Matricula::with('user')->where('curso_id', $selectedCursoId)->orderBy('user_id')->get();
        } else {
            $estudiantes = User::join('roles', 'users.roles_id', '=', 'roles.id')
                ->where('roles.nombre', 'Estudiante')
                ->select('users.id', 'users.name')
                ->orderBy('users.name')
                ->get();
        }

        return view('gestion-academica.asistencias.create', compact('cursos', 'estudiantes', 'selectedCursoId', 'selectedEstudianteId', 'matriculas'));
    }

    public function store(Request $request)
    {
        list($isEstudiante) = $this->getStudentCourseInfo();
        if ($isEstudiante) {
            abort(403, 'Estudiantes no pueden registrar asistencias');
        }
        $validated = $request->validate([
            'fecha' => ['required', 'date'],
            'curso_id' => ['required', 'integer', 'exists:cursos,id'],
            'estudiante_id' => ['required', 'integer', 'exists:users,id'],
            'presente' => ['nullable', 'boolean'],
            'observacion' => ['nullable', 'string'],
        ]);

        if (!$this->canManageAttendance($validated['curso_id'])) {
            abort(403, 'No autorizado para registrar asistencias en este curso');
        }

        // intentar buscar matrícula si existe
        $matricula = Matricula::where('curso_id', $validated['curso_id'])
            ->where('user_id', $validated['estudiante_id'])
            ->first();

        // Validar duplicado: misma fecha+curso+materia+estudiante
        $materiaId = $request->input('materia_id') ?: null;
        $duplicateQuery = Asistencia::where('curso_id', $validated['curso_id'])
            ->whereDate('fecha', $validated['fecha'])
            ->where('estudiante_id', $validated['estudiante_id']);
        if ($materiaId === null) {
            $duplicateQuery->whereNull('materia_id');
        } else {
            $duplicateQuery->where('materia_id', $materiaId);
        }

        if ($duplicateQuery->exists()) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['error' => 'Ya existe un registro de asistencia para este estudiante en la misma fecha/curso/materia.'], 422);
            }
            return redirect()->back()->withInput()->with('error', 'Ya existe un registro de asistencia para este estudiante en la misma fecha/curso/materia.');
        }

        $asistencia = Asistencia::create([
            'fecha' => $validated['fecha'],
            'curso_id' => $validated['curso_id'],
            'matricula_id' => $matricula ? $matricula->id : null,
            'materia_id' => $request->input('materia_id') ?: null,
            'estudiante_id' => $validated['estudiante_id'],
            'presente' => !empty($validated['presente']),
            'observacion' => $validated['observacion'] ?? null,
        ]);

        return redirect()->route('asistencias.index', ['curso_id' => $validated['curso_id']])->with('success', 'Asistencia registrada correctamente.');
    }

    public function edit($id)
    {
        list($isEstudiante) = $this->getStudentCourseInfo();
        if ($isEstudiante) {
            abort(403, 'Estudiantes no pueden editar asistencias');
        }
        $asistencia = Asistencia::findOrFail($id);
        if (!$this->canManageAttendance($asistencia->curso_id)) {
            abort(403);
        }
        $cursos = Curso::orderBy('nombre')->get();
        $estudiantes = User::join('roles', 'users.roles_id', '=', 'roles.id')
            ->where('roles.nombre', 'Estudiante')
            ->select('users.id', 'users.name')
            ->orderBy('users.name')
            ->get();

        return view('gestion-academica.asistencias.edit', compact('asistencia', 'cursos', 'estudiantes'));
    }

    public function update(Request $request, $id)
    {
        list($isEstudiante) = $this->getStudentCourseInfo();
        if ($isEstudiante) {
            abort(403, 'Estudiantes no pueden modificar asistencias');
        }
        $asistencia = Asistencia::findOrFail($id);

        if (!$this->canManageAttendance($asistencia->curso_id)) {
            abort(403, 'No autorizado para modificar esta asistencia');
        }

        // Sólo permitimos cambiar presencia y observación en la edición individual.
        $validated = $request->validate([
            'presente' => ['nullable', 'boolean'],
            'observacion' => ['nullable', 'string'],
        ]);

        $asistencia->update([
            'presente' => !empty($validated['presente']),
            'observacion' => $validated['observacion'] ?? null,
        ]);

        return redirect()->route('asistencias.index')->with('success', 'Asistencia actualizada correctamente.');
    }

    public function destroy($id)
    {
        list($isEstudiante) = $this->getStudentCourseInfo();
        if ($isEstudiante) {
            abort(403, 'Estudiantes no pueden eliminar asistencias');
        }
        $asistencia = Asistencia::findOrFail($id);
        if (!$this->canManageAttendance($asistencia->curso_id)) {
            abort(403, 'No autorizado para eliminar esta asistencia');
        }
        $asistencia->delete();
        return redirect()->route('asistencias.index')->with('success', 'Asistencia eliminada.');
    }

    /**
     * Exportar una sola asistencia a PDF.
     */
    public function exportSingle($id)
    {
        list($isEstudiante) = $this->getStudentCourseInfo();
        if ($isEstudiante) {
            abort(403, 'Estudiantes no pueden exportar asistencias');
        }
        $asistencia = Asistencia::with(['curso', 'estudiante', 'materia'])->findOrFail($id);

        if (!$this->canManageAttendance($asistencia->curso_id)) {
            abort(403, 'No autorizado para exportar esta asistencia');
        }

        $asistencias = collect([$asistencia]);

        // Calcular contadores para ese registro específico (fecha+curso+materia)
        $groupStats = [];
        if ($asistencia->curso_id) {
            $fecha = $asistencia->fecha ? \Carbon\Carbon::parse($asistencia->fecha)->format('Y-m-d') : null;
            $cursoId = $asistencia->curso_id;
            $materiaId = $asistencia->materia_id;

            $total = \App\Models\Matricula::where('curso_id', $cursoId)->count();
            $asq = Asistencia::where('curso_id', $cursoId)->whereDate('fecha', $fecha);
            if ($materiaId === null) {
                $asq->whereNull('materia_id');
            } else {
                $asq->where('materia_id', $materiaId);
            }
            $present = (clone $asq)->where('presente', 1)->count();
            $absent = (clone $asq)->where('presente', 0)->count();
            $excuse = (clone $asq)->whereNotNull('observacion')->where('observacion', '<>', '')->count();

            $cursoNombre = optional(\App\Models\Curso::find($cursoId))->nombre;
            $materiaNombre = $materiaId ? optional(\App\Models\Materia::find($materiaId))->nombre : null;

            $key = md5(($fecha ?? '') . '_' . $cursoId . '_' . ($materiaId ?? 'NULL'));
            $groupStats[$key] = [
                'fecha' => $fecha,
                'curso_id' => $cursoId,
                'curso_nombre' => $cursoNombre,
                'materia_id' => $materiaId,
                'materia_nombre' => $materiaNombre,
                'total' => $total,
                'present' => $present,
                'absent' => $absent,
                'excuse' => $excuse,
            ];
        }

        // Generar PDF usando Dompdf (reutiliza la vista export_pdf)
        $html = view('gestion-academica.asistencias.export_pdf', compact('asistencias', 'groupStats'))->render();

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $filename = 'asistencia_'.$asistencia->id.'_'.date('Ymd_His').'.pdf';
        $output = $dompdf->output();
        return response($output, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Mostrar formulario de registro por curso (lista de estudiantes matriculados).
     */
    public function registroPorCurso($cursoId)
    {
        list($isEstudiante, $studentCourseId) = $this->getStudentCourseInfo();
        // Si es estudiante, sólo puede ver su propio curso
        if ($isEstudiante && $studentCourseId != $cursoId) {
            abort(403, 'No autorizado para ver el registro de este curso');
        }
        $curso = Curso::findOrFail($cursoId);
        if (!$this->canManageAttendance($cursoId) && ! $isEstudiante) {
            abort(403, 'No autorizado para ver el registro de este curso');
        }
        $matriculas = Matricula::with('user')->where('curso_id', $cursoId)->orderBy('user_id')->get();
        // Allow passing a date via querystring (e.g. ?fecha=2025-11-19)
        $fecha = request()->query('fecha', date('Y-m-d'));
        $materiaId = request()->query('materia_id') ?: null;
        $materias = \App\Models\Materia::where('curso_id', $cursoId)->orderBy('nombre')->get();

        // Cargar asistencias existentes para esta fecha/curso y materia (si se pasa)
        $existingQuery = Asistencia::where('curso_id', $cursoId)->whereDate('fecha', $fecha);
        if ($materiaId === null) {
            $existingQuery->whereNull('materia_id');
        } else {
            $existingQuery->where('materia_id', $materiaId);
        }
        $existingAsistencias = $existingQuery->get()->keyBy('matricula_id');

        return view('gestion-academica.asistencias.registro_curso', compact('curso', 'matriculas', 'fecha', 'materiaId', 'materias', 'existingAsistencias'));
    }

    /**
     * Devuelve la vista parcial (HTML) del formulario masivo para inyección por AJAX.
     */
    public function partialRegistro(Request $request, $cursoId)
    {
        list($isEstudiante, $studentCourseId) = $this->getStudentCourseInfo();
        if ($isEstudiante && $studentCourseId != $cursoId) {
            abort(403, 'No autorizado para ver el registro de este curso');
        }
        $curso = Curso::findOrFail($cursoId);
        if (!$this->canManageAttendance($cursoId) && ! $isEstudiante) {
            abort(403, 'No autorizado para ver el registro de este curso');
        }
        $matriculas = Matricula::with('user')->where('curso_id', $cursoId)->orderBy('user_id')->get();
        $fecha = $request->query('fecha', date('Y-m-d'));
        $materiaId = $request->query('materia_id') ?: null;
        $materias = \App\Models\Materia::where('curso_id', $cursoId)->orderBy('nombre')->get();
        $existingAsistencias = Asistencia::where('curso_id', $cursoId)
            ->whereDate('fecha', $fecha)
            ->get()
            ->keyBy('matricula_id');

        // Renderizar vista parcial y devolver HTML
        $html = view('gestion-academica.asistencias._partial_registro', compact('curso', 'matriculas', 'fecha', 'materiaId', 'materias', 'existingAsistencias'))->render();
        return response($html, 200)->header('Content-Type', 'text/html');
    }

    /**
     * Guardar asistencias masivas para un curso en una fecha determinada.
     */
    public function storeMultiple(Request $request, $cursoId)
    {
        list($isEstudiante, $studentCourseId) = $this->getStudentCourseInfo();
        if ($isEstudiante) {
            abort(403, 'Estudiantes no pueden registrar asistencias');
        }
        if (!$this->canManageAttendance($cursoId)) {
            abort(403, 'No autorizado para registrar asistencias en este curso');
        }
        $validated = $request->validate([
            'fecha' => ['required', 'date'],
            'observations' => ['nullable', 'array'],
            'observations.*' => ['nullable', 'string'],
        ]);

        $fecha = $validated['fecha'];
        $observations = $request->input('observations', []);
        $materiaId = $request->input('materia_id') ?: null;

        // Detectar existentes considerando materia (si se pasó)
        $existingQuery = Asistencia::where('curso_id', $cursoId)->whereDate('fecha', $fecha);
        if ($materiaId === null) {
            $existingQuery->whereNull('materia_id');
        } else {
            $existingQuery->where('materia_id', $materiaId);
        }
        $existingAsistencias = $existingQuery->get()->keyBy('matricula_id');

        // Si el formulario incluye 'original_fecha' (modo edición real), validar inmutabilidad:
        $originalFecha = $request->input('original_fecha');
        $originalMateriaId = $request->input('original_materia_id');
        if ($originalFecha !== null) {
            // Comprobar que existan registros para la combinación original
            $origQuery = Asistencia::where('curso_id', $cursoId)->whereDate('fecha', $originalFecha);
            if ($originalMateriaId === '' || $originalMateriaId === null) {
                $origQuery->whereNull('materia_id');
            } else {
                $origQuery->where('materia_id', $originalMateriaId);
            }
            $origExists = $origQuery->exists();
            if ($origExists) {
                // Forzar que la fecha y materia enviadas coincidan con las originales
                $sentFecha = $request->input('fecha');
                $sentMateria = $request->input('materia_id') ?: null;
                $origMateriaNormalized = ($originalMateriaId === '' ? null : $originalMateriaId);
                if ($sentFecha != $originalFecha || ($sentMateria != $origMateriaNormalized)) {
                    abort(403, 'No está permitido cambiar la fecha o materia de un registro existente.');
                }
            }
        }
        // Nota: eliminamos la validación rígida que bloqueaba operaciones masivas
        // cuando ya existían registros para la misma combinación fecha+curso+materia.
        // Ahora permitimos que el flujo use updateOrCreate para actualizar registros
        // existentes (edición) o crearlos si faltan (nuevo registro por estudiante).

        // Cargar asistencias existentes para respetar bandera definitiva
        $existingAsistencias = Asistencia::where('curso_id', $cursoId)
            ->whereDate('fecha', $fecha)
            ->get()
            ->keyBy('matricula_id');

        // Cargar matrículas del curso para obtener matricula_id y user_id
        $matriculas = Matricula::where('curso_id', $cursoId)->get()->keyBy('id');

        // Si no se enviaron 'statuses' (por algún motivo) creamos valores por defecto 'absent'
        $statuses = $request->input('statuses', []);
        if (empty($statuses)) {
            foreach ($matriculas as $mid => $mobj) {
                $statuses[$mid] = 'absent';
            }
        }

        foreach ($statuses as $matriculaId => $status) {
            if (!isset($matriculas[$matriculaId])) {
                // ignorar entradas que no correspondan
                continue;
            }

            $mat = $matriculas[$matriculaId];
            $userId = $mat->user_id;

            // Representación canonical de estados:
            // - present  => presente = true
            // - absent   => presente = false
            // - excuse   => presente = null (para distinguir de 'absent')
            // Guardar 'presente' como booleano: true para present, false en otro caso.
            // La distinción 'Excusa' se realiza por la presencia de 'observacion'.
            $present = ($status === 'present');
            // Sólo considerar observación cuando el estado es 'excuse'.
            // Esto evita que una observación previa permanezca cuando se cambia
            // el estado a 'absent' o 'present'.
            $obs = null;
            if ($status === 'excuse') {
                $obs = isset($observations[$matriculaId]) ? trim((string)$observations[$matriculaId]) : null;
                if (empty($obs)) {
                    $obs = 'Excusa';
                }
            }

            // Debug: registrar lo que llega para este matriculaId
            try {
                Log::info('asistencias.storeMultiple', ['matriculaId' => $matriculaId, 'status' => $status, 'present' => $present, 'obs' => $obs, 'userId' => $userId, 'materia' => $materiaId, 'fecha' => $fecha]);
            } catch (\Throwable $e) {}

            $isEditMode = $request->input('is_edit_mode');
            if ($isEditMode && !isset($existingAsistencias[$matriculaId])) {
                // En modo edición NO crear nuevas filas: solo actualizar las existentes
                continue;
            }

            if ($isEditMode && isset($existingAsistencias[$matriculaId])) {
                // Actualizar el registro existente identificado por matricula_id
                $existing = $existingAsistencias[$matriculaId];
                try {
                    $existing->update([
                        'matricula_id' => $mat->id,
                        'materia_id' => $materiaId,
                        'presente' => $present,
                        'observacion' => $obs,
                    ]);
                    try {
                        Log::info('asistencias.updated', ['id' => $existing->id, 'matricula_id' => $mat->id, 'observacion' => $existing->observacion]);
                    } catch (\Throwable $e) {}
                } catch (\Throwable $e) {
                    // Fallback: si falla por cualquier razón, usar updateOrCreate
                    $model = Asistencia::updateOrCreate(
                        [
                            'fecha' => $fecha,
                            'curso_id' => $cursoId,
                            'materia_id' => $materiaId,
                            'estudiante_id' => $userId,
                        ],
                        [
                            'matricula_id' => $mat->id,
                            'materia_id' => $materiaId,
                            'presente' => $present,
                            'observacion' => $obs,
                        ]
                    );
                    try {
                        if (isset($model->id)) {
                            Log::info('asistencias.updated_fallback', ['id' => $model->id, 'matricula_id' => $mat->id, 'observacion' => $model->observacion]);
                        }
                    } catch (\Throwable $e) {}
                }
            } else {
                $model = Asistencia::updateOrCreate(
                    [
                        'fecha' => $fecha,
                        'curso_id' => $cursoId,
                        'materia_id' => $materiaId,
                        'estudiante_id' => $userId,
                    ],
                    [
                        'matricula_id' => $mat->id,
                        'materia_id' => $materiaId,
                        'presente' => $present,
                        'observacion' => $obs,
                    ]
                );
                try {
                    if (isset($model->id)) {
                        Log::info('asistencias.created_or_updated', ['id' => $model->id, 'matricula_id' => $mat->id, 'observacion' => $model->observacion]);
                    }
                } catch (\Throwable $e) {}
            }
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['message' => 'Asistencias guardadas correctamente.']);
        }

        return redirect()->route('asistencias.index', ['curso_id' => $cursoId])->with('success', 'Asistencias guardadas correctamente.');
    }

    /**
     * Verifica si el usuario autenticado puede gestionar asistencias para un curso.
     * Permite: administradores, roles con 'admin' o 'rector' en el nombre, y docentes asignados al curso.
     */
    protected function canManageAttendance($cursoId = null)
    {
        try {
            $user = Auth::user();
        } catch (\Throwable $e) {
            return false;
        }

        if (!$user) return false;

        $roleNombre = optional($user->role)->nombre ?? '';
        $roleLower = mb_strtolower($roleNombre);

        if ($user->roles_id == 1 || stripos($roleNombre, 'admin') !== false || stripos($roleNombre, 'administrador') !== false || stripos($roleLower, 'rector') !== false) {
            return true;
        }

        // Si no se pasa cursoId, solo roles administrativos pueden acceder
        if (!$cursoId) return false;

        // Comprobar si el usuario es docente asignado al curso
        $curso = Curso::find($cursoId);
        if (!$curso) return false;

        return $curso->docentes()->where('docente_id', $user->id)->exists();
    }
}
