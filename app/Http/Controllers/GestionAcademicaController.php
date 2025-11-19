<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Horario;
use App\Models\Curso;
use App\Models\Materia;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class GestionAcademicaController extends Controller
{
    public function index()
    {
        // Cargamos cursos y docentes para permitir la asignación desde la tarjeta/modal
        try {
            $cursos = Curso::all();
        } catch (\Throwable $e) {
            logger()->warning('No se pudieron cargar cursos en index: ' . $e->getMessage());
            $cursos = collect();
        }

        // Obtener docentes (por rol 'Docente' o fallback por nombre)
        try {
            $docenteRole = \App\Models\RolesModel::where('nombre', 'Docente')->first();
            if ($docenteRole) {
                $docentes = \App\Models\User::where('roles_id', $docenteRole->id)->get();
            } else {
                $docentes = \App\Models\User::whereHas('role', function($q){ $q->where('nombre', 'LIKE', '%Docente%'); })->get();
            }
        } catch (\Throwable $e) {
            logger()->warning('No se pudieron cargar docentes en index: ' . $e->getMessage());
            $docentes = collect();
        }

        return view('gestion.index', compact('cursos', 'docentes'));
    }

    protected function authorizeAcademica()
    {
        $user = Auth::user();
        if ($user instanceof User && method_exists($user, 'hasPermission') && $user->hasPermission('gestionar_academica')) {
            Log::info('authorizeAcademica: permiso gestionar_academica true', ['user_id' => $user->id ?? null, 'roles_id' => $user->roles_id ?? null]);
            return true;
        }
        // Permitir administradores legacy (roles_id == 1)
        if ($user && isset($user->roles_id) && (int)$user->roles_id === 1) {
            Log::info('authorizeAcademica: legacy admin roles_id==1', ['user_id' => $user->id ?? null]);
            return true;
        }
        // Permitir si el nombre del rol contiene 'admin', 'administrador', 'rector'
        // o si es específicamente 'coordinador academico' (aceptando variantes sin tildes/ortográficas)
        if ($user && optional($user->role)->nombre) {
            $n = optional($user->role)->nombre;
            $nNorm = mb_strtolower($n);
            $nNorm = strtr($nNorm, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','Á'=>'a','É'=>'e','Í'=>'i','Ó'=>'o','Ú'=>'u']);

            if (stripos($n, 'admin') !== false || stripos($n, 'administrador') !== false || stripos($n, 'rector') !== false) {
                Log::info('authorizeAcademica: rol name matched allow', ['user_id' => $user->id ?? null, 'role_nombre' => $n]);
                return true;
            }

            // Permitir coordinador academico (acepta 'coordinador academico', 'cordinador academico', etc.)
            if (mb_stripos($nNorm, 'coordinador academ') !== false || mb_stripos($nNorm, 'cordinador academ') !== false) {
                Log::info('authorizeAcademica: rol name matched coordinador academico allow', ['user_id' => $user->id ?? null, 'role_nombre' => $n]);
                return true;
            }

            Log::info('authorizeAcademica: rol name did not match', ['user_id' => $user->id ?? null, 'role_nombre' => $n]);
        } else {
            Log::info('authorizeAcademica: no role present or user null', ['user_id' => $user->id ?? null]);
        }
        Log::warning('authorizeAcademica: aborting - acceso no autorizado', ['user_id' => $user->id ?? null, 'roles_id' => $user->roles_id ?? null, 'role_nombre' => optional($user->role)->nombre ?? null]);
        abort(403, 'Acceso no autorizado a Gestión Académica');
    }

    // 📘 CURSOS

    public function crearCurso()
    {
        $this->authorizeAcademica();
        return view('gestion.crear_curso');
    }

    public function guardarCurso(Request $request)
    {
        $this->authorizeAcademica();
        $request->validate([
            'nivel' => 'required|string',
            'grupo' => 'required|string',
            'descripcion' => 'nullable|string',
        ]);

        Curso::create([
            'nombre' => $request->nivel . ' ' . $request->grupo,
            'descripcion' => $request->descripcion,
        ]);

        return redirect()->route('cursos.panel')->with('success', 'Curso creado correctamente.');
    }

    public function listarCursos()
    {
        $cursos = Curso::all();
        return view('gestion.index', compact('cursos'));
    }

    public function editarCurso($id)
    {
        $this->authorizeAcademica();
        $curso = Curso::with('docentes')->findOrFail($id);

        // Cargar las materias pertenecientes a este curso junto con su docente
        try {
            $materias = Materia::where('curso_id', $id)->with('docente')->get();
        } catch (\Throwable $e) {
            logger()->warning('No se pudieron cargar materias en editarCurso: ' . $e->getMessage());
            $materias = collect();
        }

        // Agrupar materias por docente_id (clave null = sin docente asignado)
        $materiasPorDocente = $materias->groupBy('docente_id');

        $docentes = $curso->docentes ?? collect();

        return view('gestion.editar_curso', compact('curso', 'docentes', 'materias', 'materiasPorDocente'));
    }

    public function actualizarCurso(Request $request, $id)
    {
        $this->authorizeAcademica();
        $request->validate([
            'nombre' => 'required|string',
            'descripcion' => 'nullable|string',
        ]);

        $curso = Curso::findOrFail($id);
        $curso->update($request->only(['nombre', 'descripcion']));

        // Redirigimos al panel de cursos para mantener consistencia con crear/guardar/eliminar
        return redirect()->route('cursos.panel')->with('success', 'Curso actualizado correctamente.');
    }

    public function eliminarCurso($id)
    {
        $this->authorizeAcademica();
        $curso = Curso::findOrFail($id);
        $curso->delete();

        return redirect()->route('cursos.panel')->with('success', 'Curso eliminado correctamente.');
    }

    public function panelCursos()
    {
        $this->authorizeAcademica();
        try {
            $cursos = Curso::all();
            $errorMessage = null;
        } catch (\Throwable $e) {
            // Registro en log para depuración y devolvemos una lista vacía con mensaje de error
            logger()->error('Error al cargar cursos: ' . $e->getMessage());
            $cursos = collect();
            $errorMessage = 'Error: la tabla de cursos no existe o la conexión a la base de datos falló. Ejecuta las migraciones (php artisan migrate) o revisa la configuración de la BD.';
        }

        return view('gestion.panel_cursos', compact('cursos', 'errorMessage'));
    }

    // 🕒 HORARIOS

    public function horarios()
    {
        $this->authorizeAcademica();
        $horarios = Horario::all();
        // Enviamos también la lista de cursos para permitir accesos relacionados (ej. asignar docentes)
        try {
            $cursos = Curso::all();
        } catch (\Throwable $e) {
            logger()->warning('No se pudieron cargar cursos al mostrar horarios: ' . $e->getMessage());
            $cursos = collect();
        }

        // Parsear cada horario para separar hora_inicio, hora_fin y materia (si existe metadata)
        // Preferimos la columna `hora_text` si existe (nueva), sino usamos `hora` para compatibilidad
        $horarios = $horarios->map(function($h){
            // Leer atributos de Eloquent usando getAttribute / acceso dinámico
            $h->hora_inicio = $h->getAttribute('hora') ?? null;
            $h->hora_fin = $h->getAttribute('hora_fin') ?? null;
            $h->materia_id = $h->getAttribute('materia_id') ?? null;
            $h->materia_nombre = null;

            if (!empty($h->materia_id)) {
                $h->materia_nombre = optional(Materia::find($h->materia_id))->nombre;
            }

            // Si no hay hora_fin o materia_nombre, intentar extraer desde hora_text (compatibilidad)
            $horaText = $h->getAttribute('hora_text');
            if ((empty($h->hora_fin) || empty($h->materia_nombre)) && !empty($horaText)) {
                $parts = explode('|m:', $horaText);
                $range = trim($parts[0] ?? '');
                $times = explode('-', $range);
                if (empty($h->hora_inicio)) $h->hora_inicio = $times[0] ?? null;
                if (empty($h->hora_fin)) $h->hora_fin = $times[1] ?? null;

                if (isset($parts[1]) && is_numeric($parts[1]) && empty($h->materia_nombre)) {
                    $parsedMateriaId = intval($parts[1]);
                    $h->materia_id = $parsedMateriaId;
                    try {
                        $materia = Materia::find($parsedMateriaId);
                        $h->materia_nombre = $materia ? $materia->nombre : null;
                    } catch (\Throwable $e) {
                        $h->materia_nombre = null;
                    }
                }
            }

            return $h;
        });

        return view('gestion.horarios', compact('horarios', 'cursos'));
    }

    // Guardar nuevo horario
    /**
     * Guardar nuevo horario
     *
     * @param \Illuminate\Http\Request $request
     */
    public function guardarHorario(Request $request)
    {
        $this->authorizeAcademica();
    // variable local para que el analizador reconozca la variable
    /** @var \Illuminate\Http\Request $req */
    $req = $request ?? request();

        $req->validate([
            'curso_id' => 'required|exists:cursos,id',
            'dia' => 'required|string',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin' => 'required|date_format:H:i',
            'materia_id' => 'nullable|exists:materias,id',
        ]);

        // Validar que hora_fin sea posterior a hora_inicio
        if (strtotime($req->hora_fin) <= strtotime($req->hora_inicio)) {
            return redirect()->back()->withErrors(['hora_fin' => 'La hora fin debe ser posterior a la hora inicio.'])->withInput();
        }

        $horaRango = $req->hora_inicio . '-' . $req->hora_fin;
        if ($req->filled('materia_id')) {
            $horaRango .= '|m:' . intval($req->materia_id);
        }

        // Obtener nombre del curso a partir del id para mantener compatibilidad con el modelo Horario
        $curso = Curso::find($req->curso_id);
        $cursoNombre = $curso ? $curso->nombre : (string) $req->curso_id;

        // Guardamos la representación completa en `hora_text` (nueva columna string).
        // Mantenemos `hora` (time) sin usar para no romper compatibilidad con el esquema existente.
        // Log para depuración: registrar los datos que llegan
        Log::info('guardarHorario - request data', [
            'curso_id' => $req->curso_id ?? null,
            'materia_id' => $req->materia_id ?? null,
            'dia' => $req->dia ?? null,
            'hora_inicio' => $req->hora_inicio ?? null,
            'hora_fin' => $req->hora_fin ?? null,
        ]);

        // Guardar también una hora compatible con el tipo time en la columna `hora`
        // usamos la hora de inicio como valor para mantener compatibilidad con el esquema
        // Además guardamos hora_fin y materia_id en columnas separadas (si existen en la BD)
        $createData = [
            'curso' => $cursoNombre,
            'dia' => $req->dia,
            'hora' => $req->hora_inicio,
            'hora_fin' => $req->hora_fin ?? null,
            'materia_id' => $req->filled('materia_id') ? intval($req->materia_id) : null,
            'hora_text' => $horaRango,
        ];

        Log::info('guardarHorario - creating Horario with', $createData);

        $horario = Horario::create($createData);
        Log::info('guardarHorario - Horario created', ['id' => $horario->id, 'attributes' => $horario->toArray()]);

        return redirect()->route('gestion.horarios')->with('success', 'Horario creado correctamente.');
    }

    /**
     * Mostrar formulario de edición de un horario
     *
     * @param int $id
     */
    public function editarHorario($id)
    {
        $this->authorizeAcademica();
        /** @var int $id */
        $idLocal = $id;
        /** @var \App\Models\Horario $horario */
        $horario = Horario::findOrFail($idLocal);

        // Parsear campos desde hora_text si es necesario (compatibilidad)
        $horario->hora_inicio = null;
        $horario->hora_fin = null;
        $horario->materia_id = $horario->materia_id ?? null;
        $horario->materia_nombre = null;

        if (!empty($horario->hora_text)) {
            $parts = explode('|m:', $horario->hora_text);
            $range = trim($parts[0] ?? '');
            $times = explode('-', $range);
            $horario->hora_inicio = $times[0] ?? null;
            $horario->hora_fin = $times[1] ?? null;
            if (isset($parts[1]) && is_numeric($parts[1])) {
                $horario->materia_id = intval($parts[1]);
            }
        }

        // Si existen columnas dedicadas, preferirlas (leer atributos Eloquent correctamente)
        if (!empty($horario->getAttribute('hora'))) {
            $horario->hora_inicio = $horario->getAttribute('hora');
        }
        if (!empty($horario->getAttribute('hora_fin'))) {
            $horario->hora_fin = $horario->getAttribute('hora_fin');
        }
        if (!empty($horario->getAttribute('materia_id'))) {
            $horario->materia_nombre = optional(Materia::find($horario->getAttribute('materia_id')))->nombre;
        } else {
            $horario->materia_nombre = null;
        }

        // Obtener materias relacionadas con el curso (intentando resolver curso por nombre)
        $materias = collect();
        try {
            $cursoModel = Curso::where('nombre', $horario->curso)->first();
            if ($cursoModel) {
                $materias = Materia::where('curso_id', $cursoModel->id)->get();
            } else {
                $materias = Materia::all();
            }
        } catch (\Throwable $e) {
            $materias = Materia::all();
        }

        return view('gestion.editar_horario', compact('horario', 'materias'));
    }

    /**
     * Actualizar un horario existente
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     */
    public function actualizarHorario(Request $request, $id)
    {
        $this->authorizeAcademica();
        /** @var \Illuminate\Http\Request $req */
        $req = $request ?? request();

        $req->validate([
            'curso' => 'required|string',
            'dia' => 'required|string',
            'hora' => 'required',
        ]);
            $req->validate([
                'curso' => 'required|string',
                'dia' => 'required|string',
                'hora_inicio' => 'required|date_format:H:i',
                'hora_fin' => 'nullable|date_format:H:i',
                'materia_id' => 'nullable|exists:materias,id',
            ]);

            /** @var int $id */
            $idLocal = $id;
            /** @var \App\Models\Horario $horario */
            $horario = Horario::findOrFail($idLocal);

            $horaRango = $req->hora_inicio . '-' . ($req->hora_fin ?? $req->hora_inicio);
            if ($req->filled('materia_id')) {
                $horaRango .= '|m:' . intval($req->materia_id);
            }

            $updateData = [
                'curso' => $req->curso,
                'dia' => $req->dia,
                'hora' => $req->hora_inicio,
                'hora_fin' => $req->hora_fin ?? null,
                'materia_id' => $req->filled('materia_id') ? intval($req->materia_id) : null,
                'hora_text' => $horaRango,
            ];

            $horario->update($updateData);

            return redirect()->route('gestion.horarios')->with('success', 'Horario actualizado correctamente.');

        return redirect()->route('gestion.horarios')->with('success', 'Horario actualizado correctamente.');
    }


    /**
     * Eliminar un horario
     *
     * @param int $id
     */
    public function eliminarHorario($id)
    {
        $this->authorizeAcademica();
        /** @var int $id */
        $idLocal = $id;
        /** @var \App\Models\Horario $horario */
        $horario = Horario::findOrFail($idLocal);
        $horario->delete();

        return redirect()->route('gestion.horarios')->with('success', 'Horario eliminado correctamente.');
    }


}
