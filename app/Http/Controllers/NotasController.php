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

class NotasController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    // Listado de notas con filtros por curso, materia y periodo
    public function index(Request $request)
    {
        $query = Nota::with(['matricula.user', 'materia']);

        if ($request->filled('curso_id')) {
            $query->whereHas('matricula', function($q) use ($request) {
                $q->where('curso_id', $request->curso_id);
            });
        }

        if ($request->filled('materia_id')) {
            $query->where('materia_id', $request->materia_id);
        }

        if ($request->filled('periodo')) {
            $query->where('periodo', $request->periodo);
        }

        $notas = $query->orderBy('periodo', 'desc')->orderBy('valor', 'desc')->paginate(25);

        $cursos = Curso::orderBy('nombre')->get();
        $materias = DB::table('materias')->orderBy('nombre')->get();

        return view('notas.index', compact('notas', 'cursos', 'materias'));
    }

    // Mostrar formulario para crear notas: elegir curso, materia, periodo y listar estudiantes
    public function create(Request $request)
    {
        $cursos = Curso::orderBy('nombre')->get();
        $materias = collect();
        $matriculas = collect();

        if ($request->filled('curso_id')) {
            $curso = Curso::find($request->curso_id);
            $materias = $curso ? $curso->materias()->orderBy('nombre')->get() : collect();
            // cargar estudiantes matriculados activos
            $matriculas = Matricula::with('user')->where('curso_id', $request->curso_id)->where('estado', 'activa')->get();
        }

        return view('notas.create', compact('cursos', 'materias', 'matriculas'));
    }

    // Guardar notas (array de notas por matricula)
    public function store(Request $request)
    {
        $request->validate([
            'materia_id' => 'required|exists:materias,id',
            'periodo' => 'required|string|max:50',
            'notas' => 'required|array',
            'notas.*.matricula_id' => 'required|exists:matriculas,id',
            'notas.*.valor' => 'required|numeric|min:0|max:100'
        ]);

        $guardar = 0;
        foreach ($request->input('notas') as $row) {
            $matriculaId = $row['matricula_id'];
            $valor = $row['valor'];
            $observ = $row['observaciones'] ?? null;

            $nota = Nota::updateOrCreate(
                [
                    'matricula_id' => $matriculaId,
                    'materia_id' => $request->materia_id,
                    'periodo' => $request->periodo
                ],
                [
                    'valor' => $valor,
                    'observaciones' => $observ
                ]
            );

            if ($nota) $guardar++;
        }

        return redirect()->route('notas.create')->with('success', "Se guardaron {$guardar} notas.");
    }

    // Editar una nota individual
    public function edit(Nota $nota)
    {
        $nota->load(['matricula.user', 'materia']);
        return view('notas.edit', compact('nota'));
    }

    // Actualizar una nota
    public function update(Request $request, Nota $nota)
    {
        $request->validate([
            'valor' => 'required|numeric|min:0|max:100',
            'observaciones' => 'nullable|string|max:2000'
        ]);

        $nota->valor = $request->valor;
        $nota->observaciones = $request->observaciones;
        $nota->save();

        return redirect()->route('notas.index')->with('success', 'Nota actualizada correctamente.');
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

        if ($request->filled('periodo')) {
            $query->where('periodo', $request->periodo);
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
