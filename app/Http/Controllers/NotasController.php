<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Nota;
use App\Models\Matricula;
use App\Models\Materia;
use App\Models\Curso;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class NotasController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');

        $this->middleware(function ($request, $next) {
            $user = Auth::user();
            if (! $user) {
                abort(403, 'Acceso no autorizado');
            }

            $roleName = optional($user->role)->nombre ?? '';

            // Bloquear explícitamente al rol 'coordinador disciplina'
            if ($roleName && stripos($roleName, 'coordinador disciplina') !== false) {
                abort(403, 'Acceso no autorizado');
            }

            // Permitir usuarios con permisos de notas o roles administrativos
            if ((method_exists($user, 'hasAnyPermission') && $user->hasAnyPermission(['ver_notas','registrar_notas','consultar_reporte_academicos'])) ||
                ($roleName && (
                    stripos($roleName, 'admin') !== false ||
                    stripos($roleName, 'administrador') !== false ||
                    stripos($roleName, 'rector') !== false
                )) ||
                (isset($user->roles_id) && (int)$user->roles_id === 1)
            ) {
                return $next($request);
            }

            // Permitir a usuarios con rol 'Estudiante' acceder únicamente a las vistas de consulta
            // (lista/visualización de sus propias notas). Bloquear otras acciones.
            if ($roleName && stripos($roleName, 'estudiante') !== false) {
                $routeName = optional($request->route())->getName();
                $allowedForStudent = ['notas.index', 'notas.matricula.ver'];
                if (in_array($routeName, $allowedForStudent)) {
                    return $next($request);
                }
                abort(403, 'Acceso no autorizado');
            }

            abort(403, 'Acceso no autorizado');
        });
    }

    /**
     * Helper: devuelve true si el usuario autenticado tiene rol Estudiante.
     */
    protected function userIsStudent()
    {
        try {
            $user = Auth::user();
        } catch (\Throwable $e) {
            $user = null;
        }
        $roleName = optional($user->role)->nombre ?? '';
        return $user && stripos($roleName, 'estudiante') !== false;
    }

    // Listado de notas con filtros por curso y materia
    public function index(Request $request)
    {
        // Nuevo comportamiento: controlamos si mostrar resultados o solo repoblar inputs
        $showResults = false;
        $lastSearch = ['curso_id' => null, 'materia_id' => null];

        $cursos = Curso::orderBy('nombre')->get();
        // Materias se cargan más abajo según el curso seleccionado (o curso del estudiante)
        $materias = collect();
        $notaCounts = [];

        // Si el usuario es Estudiante, determinar su curso y forzar el filtro por curso.
        $studentCourseFixed = false;
        $studentCourseId = null;
        $currentUser = Auth::user();
        $currentRole = optional($currentUser->role)->nombre ?? '';
        if ($currentUser && stripos($currentRole, 'estudiante') !== false) {
            $lastMat = Matricula::where('user_id', $currentUser->id)
                ->where('estado', 'activo')
                ->orderByDesc('id')
                ->first();
            if ($lastMat) {
                $studentCourseId = $lastMat->curso_id;
                $studentCourseFixed = true;
                // Forzar curso_id en la request para que solo se apliquen filtros de materia
                $request->merge(['curso_id' => $studentCourseId]);
                // Reemplazar la lista de cursos por solo el curso asignado al estudiante
                $cursos = Curso::where('id', $studentCourseId)->orderBy('nombre')->get();
            }
        }

        // Si el usuario es Acudiente, cargar lista de estudiantes asociados.
        $acudienteStudents = collect();
        $selectedEstudianteId = null;
        if ($currentUser && stripos($currentRole, 'acudiente') !== false) {
            $acudienteStudents = User::where('acudiente_id', $currentUser->id)->orderBy('name')->get();
            $selectedEstudianteId = $request->input('estudiante_id', null);

            // Si se seleccionó estudiante y no se envió curso_id, auto-popular curso según su matrícula activa
            if ($selectedEstudianteId && ! $request->filled('curso_id')) {
                $lastMatEst = Matricula::where('user_id', $selectedEstudianteId)
                    ->where('estado', 'activo')
                    ->orderByDesc('id')
                    ->first();
                if ($lastMatEst) {
                    $request->merge(['curso_id' => $lastMatEst->curso_id]);
                }
            }
        }

        // Crear un mapa estudiante_id => curso_id (última matrícula activa) para uso en JS
        $estudianteCursoMap = [];
        if ($acudienteStudents->count() > 0) {
            foreach ($acudienteStudents as $stu) {
                $cursoId = Matricula::where('user_id', $stu->id)
                    ->where('estado', 'activo')
                    ->orderByDesc('id')
                    ->value('curso_id');
                $estudianteCursoMap[$stu->id] = $cursoId;
            }
        }

        // Validaciones estrictas: si el usuario es Acudiente y seleccionó un estudiante,
        // verificar que ese estudiante realmente le pertenezca. Si no, no mostrar nada.
        $isAcudiente = ($currentUser && stripos($currentRole, 'acudiente') !== false);
        if ($isAcudiente && $selectedEstudianteId) {
            $allowedIds = $acudienteStudents->pluck('id')->all();
            if (! in_array($selectedEstudianteId, $allowedIds)) {
                // Búsqueda inválida: devolver la vista sin resultados
                $notas = null;
                $showResults = false;
                return view('notas.index', compact('notas', 'cursos', 'materias', 'notaCounts', 'showResults', 'lastSearch', 'studentCourseFixed', 'studentCourseId', 'acudienteStudents', 'selectedEstudianteId', 'estudianteCursoMap'));
            }

            // Además validar que el curso seleccionado (si llega) coincida con la matrícula del estudiante
            $lastMatEst = Matricula::where('user_id', $selectedEstudianteId)
                ->where('estado', 'activo')
                ->orderByDesc('id')
                ->first();
            if ($lastMatEst && $request->filled('curso_id') && $request->curso_id != $lastMatEst->curso_id) {
                // Inconsistencia: el curso seleccionado no pertenece al estudiante -> no mostrar
                $notas = null;
                $showResults = false;
                return view('notas.index', compact('notas', 'cursos', 'materias', 'notaCounts', 'showResults', 'lastSearch', 'studentCourseFixed', 'studentCourseId', 'acudienteStudents', 'selectedEstudianteId', 'estudianteCursoMap'));
            }
        }

        // Cargar materias filtradas por curso (si está presente en la request)
        if ($request->filled('curso_id')) {
            // Usar la tabla pivote `curso_materia` para soportar materias asignadas a varios cursos
            $materias = DB::table('materias')
                ->join('curso_materia', 'materias.id', '=', 'curso_materia.materia_id')
                ->where('curso_materia.curso_id', $request->curso_id)
                ->orderBy('materias.nombre')
                ->select('materias.*')
                ->get();
        } else {
            $materias = DB::table('materias')->orderBy('nombre')->get();
        }

        // Mostrar resultados cuando se tenga curso + materia, o cuando el acudiente haya seleccionado
        // un estudiante (en cuyo caso el controlador previamente habrá auto-populado curso_id).
        $isAcudiente = ($currentUser && stripos($currentRole, 'acudiente') !== false);
        if ($request->filled('curso_id') && ($request->filled('materia_id') || ($isAcudiente && $selectedEstudianteId))) {
            $showResults = true;
            // Guardar última búsqueda; si no hay materia_id, guardamos null para mantener inputs
            session()->put('notas_last_search', ['curso_id' => $request->curso_id, 'materia_id' => $request->input('materia_id', null)]);
            $lastSearch = session('notas_last_search');

            $cursoId = $request->curso_id;
            $materiaId = $request->input('materia_id', null);

            // Si el usuario es Acudiente y no hay materia seleccionada, construir la lista
            // a partir de los estudiantes asociados (incluir estudiantes sin matrícula activa)
            $currentUser = Auth::user();
            $currentRole = optional($currentUser->role)->nombre ?? '';

            if ($currentUser && stripos($currentRole, 'acudiente') !== false && ! $materiaId) {
                $items = collect();
                $matriculasGrouped = collect();

                foreach ($acudienteStudents as $stu) {
                    $lastMat = Matricula::where('user_id', $stu->id)
                        ->where('estado', 'activo')
                        ->orderByDesc('id')
                        ->first();

                    $rep = $lastMat ?: null;

                    $item = new \stdClass();
                    if ($rep) {
                        $item->matricula = $rep;
                    } else {
                        // construir un objeto mínimo para que la vista pueda mostrar el nombre
                        $fakeMat = new \stdClass();
                        $fakeMat->id = null;
                        $fakeMat->user = $stu;
                        $fakeMat->curso_id = null;
                        $item->matricula = $fakeMat;
                    }
                    $item->materia = null;
                    $item->nota = null;
                    $item->calificacion = null;
                    $item->aprobada_calc = false;

                    $items->push($item);

                    // Para conteo de notas, agrupar por user id
                    $matriculasGrouped[$stu->id] = collect();
                    if ($rep && $rep->id) $matriculasGrouped[$stu->id]->push($rep);
                }
            } else {
                $matriculaQuery = Matricula::with('user')
                    ->where('curso_id', $cursoId)
                    ->where('estado', 'activo');

                // Si el usuario es Estudiante, sólo devolver sus propias matrículas
                if ($currentUser && stripos($currentRole, 'estudiante') !== false) {
                    $matriculaQuery->where('user_id', $currentUser->id);
                }

                // Si el usuario es Acudiente, limitar a los estudiantes asociados
                if ($currentUser && stripos($currentRole, 'acudiente') !== false) {
                    $allowedIds = $acudienteStudents->pluck('id')->all();
                    if (empty($allowedIds)) {
                        // Forzar consulta vacía si no tiene estudiantes
                        $matriculaQuery->whereRaw('1 = 0');
                    } else {
                        $matriculaQuery->whereIn('user_id', $allowedIds);
                    }
                }

                $matriculas = $matriculaQuery->get()
                    ->sortBy(function($m){ return optional($m->user)->name; })
                    ->values();

                $matriculasGrouped = $matriculas->groupBy(function($m){
                    return optional($m->user)->id ?? $m->id;
                });

                $matriculas = $matriculasGrouped->map(function($group){
                    return $group->sortByDesc('id')->first();
                })->values();

                $items = collect();

                // Si hay materia seleccionada, calcular calificaciones como antes; si no, mostrar lista básica
                foreach ($matriculasGrouped as $userId => $group) {
                    $matIds = $group->pluck('id')->all();
                    $representativeMat = $group->sortByDesc('id')->first();

                    $item = new \stdClass();
                    $item->matricula = $representativeMat;
                    $item->materia = $materiaId ? DB::table('materias')->where('id', $materiaId)->first() : null;
                    $item->nota = null;

                    if ($materiaId) {
                        $notasAlumno = Nota::with('actividades')
                            ->whereIn('matricula_id', $matIds)
                            ->where('materia_id', $materiaId)
                            ->get();

                        if ($notasAlumno->count() > 0) {
                            $califs = $notasAlumno->map(function($n){
                                if ($n->actividades && $n->actividades->count() > 0) {
                                    return round($n->actividades->avg('valor'), 2);
                                }
                                $v = floatval($n->valor);
                                if ($v <= 5.0) return round($v, 2);
                                return round(($v / 100.0) * 5.0, 2);
                            });

                            $avg = round($califs->avg(), 2);
                            $item->calificacion = $avg;
                            $item->aprobada_calc = ($avg >= 3.0);
                            $item->nota = $notasAlumno->sortByDesc('id')->first();
                        } else {
                            $item->calificacion = null;
                            $item->aprobada_calc = false;
                        }
                    } else {
                        // Sin materia seleccionada, dejamos calificación vacía; el enlace "Notas" llevará a la vista por matrícula
                        $item->calificacion = null;
                        $item->aprobada_calc = false;
                    }

                    $items->push($item);
                }
            }

            $perPage = 25;
            $page = LengthAwarePaginator::resolveCurrentPage();
            $slice = $items->slice(($page - 1) * $perPage, $perPage)->values();
            $notas = new LengthAwarePaginator($slice, $items->count(), $perPage, $page, [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'query' => $request->query()
            ]);

            foreach ($matriculasGrouped as $userId => $group) {
                $matIds = $group->pluck('id')->all();
                if ($materiaId) {
                    $count = Nota::whereIn('matricula_id', $matIds)
                                ->where('materia_id', $materiaId)
                                ->count();
                } else {
                    $count = Nota::whereIn('matricula_id', $matIds)->count();
                }
                $repMat = $group->sortByDesc('id')->first();
                if ($repMat) {
                    $notaCounts[$userId] = $count;
                }
            }

            return view('notas.index', compact('notas', 'cursos', 'materias', 'notaCounts', 'showResults', 'lastSearch', 'studentCourseFixed', 'studentCourseId', 'acudienteStudents', 'selectedEstudianteId', 'estudianteCursoMap'));
        }

        // No hay filtros en request: rellenar inputs con última búsqueda si existe, pero no mostrar resultados
        if (session()->has('notas_last_search')) {
            $lastSearch = session('notas_last_search');
        }

        $notas = null;
        return view('notas.index', compact('notas', 'cursos', 'materias', 'notaCounts', 'showResults', 'lastSearch', 'studentCourseFixed', 'studentCourseId', 'acudienteStudents', 'selectedEstudianteId', 'estudianteCursoMap'));
    }

    // Mostrar formulario para crear notas (soporta matricula_id o curso_id+materia_id)
    public function create(Request $request)
    {
        // Prohibir creación de notas por parte del rol Rector o Coordinador Académico
        $user = Auth::user();
        $roleName = optional($user->role)->nombre ?? '';
        if ($user && (stripos($roleName, 'rector') !== false || stripos($roleName, 'coordinador') !== false)) {
            abort(403, 'No tienes permiso para crear notas.');
        }

        // Prohibir creación de notas por parte del rol Estudiante
        if ($this->userIsStudent()) {
            abort(403, 'No tienes permiso para crear notas.');
        }

        $cursos = Curso::orderBy('nombre')->get();
        $materias = DB::table('materias')->orderBy('nombre')->get();
        $matriculas = collect();

        $selectedMateriaId = $request->input('materia_id', null);
        $selectedAnio = $request->input('anio', date('Y'));
        $back = $request->query('back', null);

        // Si el parámetro materia_id llegó mal codificado (p. ej. "3?back=..."), normalizar
        if ($selectedMateriaId && is_string($selectedMateriaId) && strpos($selectedMateriaId, '?') !== false) {
            $parts = explode('?', $selectedMateriaId, 2);
            $selectedMateriaId = $parts[0];
            if (! $back && isset($parts[1])) {
                parse_str($parts[1], $qs);
                if (isset($qs['back'])) {
                    $back = $qs['back'];
                    // colocar en la request para que la vista lo lea por request('back') si es necesario
                    $request->query->set('back', $back);
                }
            }
        }

        // Asegurar que la request refleje la materia_id normalizada (evitar "3?back=...")
        if ($selectedMateriaId) {
            $request->query->set('materia_id', $selectedMateriaId);
            $request->merge(['materia_id' => $selectedMateriaId]);
        }

        if ($request->filled('matricula_id')) {
            $mat = Matricula::with(['user', 'curso'])->find($request->matricula_id);
            if ($mat) $matriculas = collect([$mat]);
        } elseif ($request->filled('curso_id') && $selectedMateriaId) {
            $matriculas = Matricula::with(['user', 'curso'])
                ->where('curso_id', $request->curso_id)
                ->where('estado', 'activo')
                ->get();
        }

        // Log debug info to help diagnose why only some users appear
        try {
            Log::info('notas.create.debug', [
                'user_id' => optional($user)->id,
                'role' => $roleName,
                'curso_id' => $request->curso_id ?? null,
                'materia_id' => $selectedMateriaId ?? null,
                'matriculas_count' => is_countable($matriculas) ? count($matriculas) : (is_object($matriculas) && method_exists($matriculas, 'count') ? $matriculas->count() : null)
            ]);
        } catch (\Throwable $e) {
            // no bloquear la vista por fallos en logging
        }

        return view('notas.create', compact('cursos', 'materias', 'matriculas', 'selectedMateriaId', 'selectedAnio'));
    }

    // Guardar notas (array de notas por matricula)
    public function store(Request $request)
    {
        // Prohibir guardar/crear notas por parte del rol Rector o Coordinador Académico
        $user = Auth::user();
        $roleName = optional($user->role)->nombre ?? '';
        if ($user && (stripos($roleName, 'rector') !== false || stripos($roleName, 'coordinador') !== false)) {
            abort(403, 'No tienes permiso para crear notas.');
        }

        // Prohibir guardar/crear notas por parte del rol Estudiante
        if ($this->userIsStudent()) {
            abort(403, 'No tienes permiso para crear notas.');
        }

        $request->validate([
            'materia_id' => 'required|integer|exists:materias,id',
            'anio' => 'nullable|string|max:50',
            'notas' => 'required|array',
            'notas.*.matricula_id' => 'required|exists:matriculas,id',
            'notas.*.valor' => 'required|numeric|min:0|max:100'
        ]);

        // Log incoming notas payload for debugging
        try {
            $rows = $request->input('notas', []);
            Log::info('notas.store.debug', [
                'user_id' => optional($user)->id,
                'role' => $roleName,
                'materia_id' => $request->materia_id ?? null,
                'notas_count' => is_array($rows) ? count($rows) : null
            ]);
        } catch (\Throwable $e) {
            // ignore logging errors
        }

        $guardar = 0;
        $lastMatriculaId = null;
        foreach ($request->input('notas') as $row) {
            $matriculaId = $row['matricula_id'];
            $valor = $row['valor'];
            $observ = $row['observaciones'] ?? null;

            // Crear una nueva nota en lugar de sobrescribir existentes.
            // Anteriormente se usaba updateOrCreate que reemplazaba notas previas
            // para la misma matricula/materia/anio. Para permitir varias notas
            // por alumno en la misma materia (y que el botón 'Agregar otra nota'
            // funcione) creamos entradas nuevas.
            // Asegurar que materia_id sea un entero limpio (evitar valores concatenados con ?back=...)
            $materiaIdClean = (int) $request->materia_id;

            $nota = Nota::create([
                'matricula_id' => $matriculaId,
                'materia_id' => $materiaIdClean,
                'anio' => $request->anio,
                'valor' => $valor,
                'observaciones' => $observ,
            ]);

            if ($nota) $guardar++;
            $lastMatriculaId = $matriculaId;
        }

        // Si guardamos al menos una nota, redirigir a la vista de notas del estudiante
        if ($guardar > 0 && $lastMatriculaId) {
            $url = route('notas.matricula.ver', $lastMatriculaId);
            if ($request->filled('back')) {
                $url .= '?back=' . urlencode($request->input('back'));
            }
            return redirect()->to($url)->with('success', "Se guardaron {$guardar} notas.");
        }

        return redirect()->route('notas.create')->with('success', "Se guardaron {$guardar} notas.");
    }

    // Editar una nota individual
    public function edit(Nota $nota)
    {
        $nota->load(['matricula.user', 'materia']);

        // Si la nota está marcada como definitiva, sólo permitir editar a Rector o Coordinador Académico
        $user = Auth::user();
        $roleName = optional($user->role)->nombre ?? null;
        $isPrivileged = ($roleName === 'Rector' || $roleName === 'Coordinador Académico' || (isset($user->roles_id) && (int)$user->roles_id === 1));

        // Prohibir edición de notas por parte del rol Estudiante
        if ($this->userIsStudent()) {
            abort(403, 'No tienes permiso para editar notas.');
        }

        if ($nota->definitiva && ! $isPrivileged) {
            abort(403, 'La nota está marcada como definitiva y no puede editarse');
        }

        return view('notas.edit', compact('nota'));
    }

    // Actualizar una nota
    public function update(Request $request, Nota $nota)
    {
        $request->validate([
            'valor' => 'required|numeric|min:0|max:100',
            'observaciones' => 'nullable|string|max:2000'
        ]);

        // Prohibir edición/actualización de notas por parte del rol Estudiante
        if ($this->userIsStudent()) {
            abort(403, 'No tienes permiso para editar notas.');
        }

        // Si la nota está marcada como definitiva, sólo permitir editar a Rector o Coordinador Académico
        $user = Auth::user();
        $roleName = optional($user->role)->nombre ?? null;
        $isPrivileged = ($roleName === 'Rector' || $roleName === 'Coordinador Académico' || (isset($user->roles_id) && (int)$user->roles_id === 1));
        if ($nota->definitiva && ! $isPrivileged) {
            abort(403, 'La nota está marcada como definitiva y no puede editarse');
        }

        $nota->valor = $request->valor;
        $nota->observaciones = $request->observaciones;
        $nota->save();

        return redirect()->route('notas.index')->with('success', 'Nota actualizada correctamente.');
    }

    // Ver notas para una matrícula (estudiante)
    public function porMatricula(Request $request, Matricula $matricula)
    {
        // Si el usuario es Estudiante, sólo puede ver sus propias matrículas
        $user = Auth::user();
        $roleName = optional($user->role)->nombre ?? '';
        if ($user && stripos($roleName, 'estudiante') !== false) {
            if ($matricula->user_id !== $user->id) {
                abort(403, 'Acceso no autorizado');
            }
        }

        // Cargar notas filtrando por matrícula
        $notasQuery = Nota::with(['materia', 'actividades'])
            ->where('matricula_id', $matricula->id);

        // Determinar si el usuario puede aplicar filtros (roles/permisos)
        $user = Auth::user();
        $canFilter = false;
        if ($user) {
            if (method_exists($user, 'hasAnyPermission') && $user->hasAnyPermission(['ver_notas','registrar_notas','consultar_reporte_academicos'])) {
                $canFilter = true;
            }
            $rname = optional($user->role)->nombre ?? '';
            if ($rname && (stripos($rname, 'admin') !== false || stripos($rname, 'rector') !== false || stripos($rname, 'docente') !== false)) {
                $canFilter = true;
            }
            if (isset($user->roles_id) && (int)$user->roles_id === 1) {
                $canFilter = true;
            }
        }

        // Si se solicitó materia_id y el usuario puede filtrar, validar que la materia pertenezca al curso de la matrícula
        if ($request->filled('materia_id') && $canFilter) {
            $materiaId = $request->materia_id;
            // Validar que la materia pertenezca al curso de la matrícula usando la tabla pivote
            $exists = DB::table('curso_materia')
                ->where('materia_id', $materiaId)
                ->where('curso_id', $matricula->curso_id)
                ->exists();
            if ($exists) {
                $notasQuery->where('materia_id', $materiaId);
            } else {
                // Forzar consulta vacía
                $notasQuery->whereRaw('1 = 0');
            }
        }

        $notas = $notasQuery->get();

        // calcular calificación 0-5
        $notas->transform(function($nota){
            if ($nota->actividades && $nota->actividades->count() > 0) {
                $nota->calificacion = round($nota->actividades->avg('valor'), 2);
            } else {
                // `valor` puede estar en escala 0-5 o 0-100. Detectar y normalizar.
                if ($nota->valor === null) {
                    $nota->calificacion = null;
                } else {
                    $v = floatval($nota->valor);
                    if ($v <= 5.0) {
                        $nota->calificacion = round($v, 2);
                    } else {
                        $nota->calificacion = round(($v / 100.0) * 5.0, 2);
                    }
                }
            }
            $nota->aprobada_calc = ($nota->calificacion !== null) ? ($nota->calificacion >= 3.0) : false;
            return $nota;
        });

        $back = $request->query('back', null);
        return view('notas.notas_estudiante', compact('matricula', 'notas', 'back'));
    }

    // Marcar nota como definitiva (no reversible desde UI)
    public function marcarDefinitiva(Request $request, Nota $nota)
    {
        $user = Auth::user();

        // Sólo Docente o Administrador_sistema (o superadmin roles_id==1) pueden marcar definitiva
        $roleName = optional($user->role)->nombre ?? null;
        $canMark = ($roleName === 'Docente' || $roleName === 'Administrador_sistema' || (isset($user->roles_id) && (int)$user->roles_id === 1));
        if (! $canMark) {
            abort(403, 'No autorizado para marcar la nota como definitiva');
        }

        $nota->definitiva = true;
        $nota->definitiva_por = $user ? $user->id : null;
        $nota->definitiva_en = now();
        $nota->save();

        return redirect()->back()->with('success', 'Nota marcada como definitiva.');
    }

    // Quitar el estado de nota definitiva (permitido a Rector y Administrador_sistema)
    public function quitarDefinitiva(Request $request, Nota $nota)
    {
        $user = Auth::user();
        $roleName = optional($user->role)->nombre ?? '';

        $canUnmark = false;
        if ($user) {
            if (stripos($roleName, 'rector') !== false || stripos($roleName, 'coordinador') !== false) {
                $canUnmark = true;
            }
            if ($roleName === 'Administrador_sistema' || (isset($user->roles_id) && (int)$user->roles_id === 1)) {
                $canUnmark = true;
            }
        }

        if (! $canUnmark) {
            abort(403, 'No autorizado para quitar la nota definitiva');
        }

        // Si no está marcada como definitiva, no hacer nada
        if (! $nota->definitiva) {
            return redirect()->back()->with('info', 'La nota no estaba marcada como definitiva.');
        }

        $nota->definitiva = false;
        $nota->definitiva_por = null;
        $nota->definitiva_en = null;
        $nota->save();

        return redirect()->back()->with('success', 'Estado de nota definitiva quitado correctamente.');
    }

    // Aprobar una nota
    public function approve(Request $request, Nota $nota)
    {
        // Solo usuarios con permiso o roles de admin pueden aprobar; permitimos por ahora roles_id == 1
        $user = Auth::user();
        // Para la iteración actual limitamos la aprobación a administradores (roles_id == 1)
        if (! $user || ! (isset($user->roles_id) && (int)$user->roles_id === 1)) {
            abort(403, 'No autorizado para aprobar notas');
        }

        $nota->aprobada = true;
        $nota->aprobado_por = $user->id;
        $nota->aprobado_en = now();
        $nota->save();

        return redirect()->route('notas.index')->with('success', 'Nota aprobada');
    }

    // Reporte simple: promedios por materia y periodo
    public function reporte(Request $request)
    {
        $query = Nota::with(['materia']);

        if ($request->filled('anio')) {
            $query->where('anio', $request->anio);
        }

        $stats = $query->select('materia_id', DB::raw('AVG(valor) as promedio'), DB::raw('COUNT(*) as total'))
                    ->groupBy('materia_id')
                    ->get()
                    ->map(function($r){
                        $r->materia = DB::table('materias')->where('id', $r->materia_id)->value('nombre');
                        return $r;
                    });

        return view('notas.reporte', compact('stats'));
    }
}
