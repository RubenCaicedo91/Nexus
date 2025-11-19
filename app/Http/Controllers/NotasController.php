<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Nota;
use App\Models\Matricula;
use App\Models\Materia;
use App\Models\Curso;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class NotasController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    // Listado de notas con filtros por curso y materia
    public function index(Request $request)
    {
        // Nuevo comportamiento: controlamos si mostrar resultados o solo repoblar inputs
        $showResults = false;
        $lastSearch = ['curso_id' => null, 'materia_id' => null];

        $cursos = Curso::orderBy('nombre')->get();
        $materias = DB::table('materias')->orderBy('nombre')->get();
        $notaCounts = [];

        if ($request->filled('curso_id') && $request->filled('materia_id')) {
            $showResults = true;
            session()->put('notas_last_search', ['curso_id' => $request->curso_id, 'materia_id' => $request->materia_id]);
            $lastSearch = session('notas_last_search');

            $cursoId = $request->curso_id;
            $materiaId = $request->materia_id;

            $matriculas = Matricula::with('user')
                ->where('curso_id', $cursoId)
                ->where('estado', 'activo')
                ->get()
                ->sortBy(function($m){ return optional($m->user)->name; })
                ->values();

            $matriculasGrouped = $matriculas->groupBy(function($m){
                return optional($m->user)->id ?? $m->id;
            });

            $matriculas = $matriculasGrouped->map(function($group){
                return $group->sortByDesc('id')->first();
            })->values();

            $items = collect();
            foreach ($matriculasGrouped as $userId => $group) {
                $matIds = $group->pluck('id')->all();

                $notasAlumno = Nota::with('actividades')
                    ->whereIn('matricula_id', $matIds)
                    ->where('materia_id', $materiaId)
                    ->get();

                $representativeMat = $group->sortByDesc('id')->first();

                $item = new \stdClass();
                $item->matricula = $representativeMat;
                $item->materia = DB::table('materias')->where('id', $materiaId)->first();
                $item->nota = null;

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

                $items->push($item);
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
                $count = Nota::whereIn('matricula_id', $matIds)
                            ->where('materia_id', $materiaId)
                            ->count();
                $repMat = $group->sortByDesc('id')->first();
                if ($repMat) {
                    $notaCounts[$userId] = $count;
                }
            }

            return view('notas.index', compact('notas', 'cursos', 'materias', 'notaCounts', 'showResults', 'lastSearch'));
        }

        // No hay filtros en request: rellenar inputs con última búsqueda si existe, pero no mostrar resultados
        if (session()->has('notas_last_search')) {
            $lastSearch = session('notas_last_search');
        }

        $notas = null;
        return view('notas.index', compact('notas', 'cursos', 'materias', 'notaCounts', 'showResults', 'lastSearch'));
    }

    // Mostrar formulario para crear notas (soporta matricula_id o curso_id+materia_id)
    public function create(Request $request)
    {
        // Prohibir creación de notas por parte del rol Rector
        $user = Auth::user();
        $roleName = optional($user->role)->nombre ?? '';
        if ($user && stripos($roleName, 'rector') !== false) {
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

        return view('notas.create', compact('cursos', 'materias', 'matriculas', 'selectedMateriaId', 'selectedAnio'));
    }

    // Guardar notas (array de notas por matricula)
    public function store(Request $request)
    {
        // Prohibir guardar/crear notas por parte del rol Rector
        $user = Auth::user();
        $roleName = optional($user->role)->nombre ?? '';
        if ($user && stripos($roleName, 'rector') !== false) {
            abort(403, 'No tienes permiso para crear notas.');
        }

        $request->validate([
            'materia_id' => 'required|integer|exists:materias,id',
            'anio' => 'nullable|string|max:50',
            'notas' => 'required|array',
            'notas.*.matricula_id' => 'required|exists:matriculas,id',
            'notas.*.valor' => 'required|numeric|min:0|max:100'
        ]);

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
        $notas = Nota::with(['materia', 'actividades'])
            ->where('matricula_id', $matricula->id)
            ->get();

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
            if (stripos($roleName, 'rector') !== false) {
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
