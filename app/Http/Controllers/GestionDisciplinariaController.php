<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sancion;
use App\Models\User;
use App\Models\RolesModel;
use App\Models\Matricula;
use Illuminate\Support\Facades\Auth;

class GestionDisciplinariaController extends Controller
{
    /**
     * Mostrar formulario para crear Sanción.
     */
    public function mostrarFormularioSancion()
    {
        // Evitar que el rol "cordinador academico" o "docente" acceda al formulario
        if ($this->isCoordinadorAcademico() || $this->isDocente()) {
            return redirect()->route('gestion-disciplinaria.index')->with('error', 'No tienes permiso para acceder a esta acción.');
        }
        // Obtener estudiantes (rol 'Estudiante') para mostrar por nombre en el select
        $studentRole = RolesModel::where('nombre', 'Estudiante')->first();
        if ($studentRole) {
            $students = User::where('roles_id', $studentRole->id)->orderBy('name')->get();
        } else {
            // Fallback: pasar todos los usuarios si no se encuentra el rol
            $students = User::orderBy('name')->get();
        }

        // Preparar arreglo simple para el cliente con id, name y display
        $studentArray = $students->map(function($s){
            return [
                'id' => $s->id,
                'name' => $s->name,
                'document_number' => $s->document_number ?? null,
                'display' => trim($s->name . ' ' . ($s->document_number ? ' - ' . $s->document_number : '') . ' (ID: ' . $s->id . ')')
            ];
        })->values()->all();

        // Cargar tipos de sanción activos para el select
        $tipos = \App\Models\SancionTipo::where('activo', true)->orderBy('nombre')->get();

        // Lista por defecto de tipos de faltas que deberían aparecer en el select.
        // Si no existen, creamos los registros por primera vez (activo = true).
        $defaultTipos = [
            'Falta leve',
            'Falta grave',
            'Retardo',
            'Inasistencia injustificada',
            'Vandalismo',
            'Acoso',
            'Agresión',
            'Desobediencia'
        ];

        foreach ($defaultTipos as $dt) {
            $exists = \App\Models\SancionTipo::whereRaw('LOWER(nombre) = ?', [mb_strtolower($dt)])->first();
            if (! $exists) {
                try {
                    \App\Models\SancionTipo::create(['nombre' => $dt, 'activo' => true, 'categoria' => 'normal']);
                } catch (\Throwable $e) {
                    // No detener el flujo si la creación falla (posible esquema diferente), continuar
                }
            }
        }

        // Recargar tipos (incluyendo los creados) y preparar datos para JS
        $tipos = \App\Models\SancionTipo::where('activo', true)->orderBy('nombre')->get();
        $tiposForJs = $tipos->map(function($t){ return ['id' => $t->id, 'nombre' => $t->nombre, 'categoria' => $t->categoria ?? 'normal']; })->values();

        $isCoordinator = $this->isCoordinadorAcademico();
        return view('gestion-disciplinaria.registrar_sancion', compact('students', 'studentArray', 'tipos', 'tiposForJs', 'isCoordinator'));
    }
    
    /**
     * Registrar Sanción.
     */
    public function registrarSancion(Request $request)
    {

        // Server-side: prohibir acción si el usuario es coordinador académico o docente
        if ($this->isCoordinadorAcademico() || $this->isDocente()) {
            abort(403, 'No tienes permiso para registrar sanciones.');
        }



        $baseRules = [
            'usuario_id' => 'required|exists:users,id',
            'descripcion' => 'required|string|max:1000',
            // permitimos elegir 'otro' por lo que validamos exists condicionalmente más abajo
            'tipo_id' => 'required',
            'fecha' => 'required|date',
        ];

        // Validación condicional según el tipo seleccionado
        // Si el usuario selecciona un tipo existente lo cargamos; si selecciona 'otro' dejamos tipoModel null
        $tipoModel = null;
        if ($request->input('tipo_id') && $request->input('tipo_id') !== 'otro') {
            $tipoModel = \App\Models\SancionTipo::find($request->input('tipo_id'));
        }

        $extraRules = [];
        $isSuspension = false;
        $isMonetary = false;
        $isExpulsion = false;
        $isPrivileges = false;
        $isMeeting = false;
        // Usar la categoría explícita si está definida
        $categoria = $tipoModel->categoria ?? null;
        if ($categoria === 'suspension') {
            $isSuspension = true;
            $extraRules['fecha_inicio'] = 'required|date';
            $extraRules['fecha_fin'] = 'required|date|after_or_equal:fecha_inicio';
        } elseif ($categoria === 'monetary') {
            $isMonetary = true;
            $extraRules['monto'] = 'required|numeric|min:0.01';
            $extraRules['pago_observacion'] = 'nullable|string';
        } elseif ($categoria === 'expulsion') {
            $isExpulsion = true;
            $extraRules['fecha_inicio'] = 'required|date';
        } elseif ($categoria === 'privileges') {
            $isPrivileges = true;
            $extraRules['fecha_inicio'] = 'required|date';
            $extraRules['fecha_fin'] = 'required|date|after_or_equal:fecha_inicio';
        } elseif ($categoria === 'meeting') {
            $isMeeting = true;
            $extraRules['reunion_at'] = 'required|date';
        } else {
            // Fallback: detectar por nombre si no hay categoría
            if ($tipoModel) {
                $lower = mb_strtolower($tipoModel->nombre);
                if (mb_strpos($lower, 'suspens') !== false || mb_strpos($lower, 'suspensión') !== false || mb_strpos($lower, 'suspension') !== false) {
                    $isSuspension = true;
                    $extraRules['fecha_inicio'] = 'required|date';
                    $extraRules['fecha_fin'] = 'required|date|after_or_equal:fecha_inicio';
                }
                if (mb_strpos($lower, 'multa') !== false || mb_strpos($lower, 'econ') !== false || mb_strpos($lower, 'sanción económica') !== false) {
                    $isMonetary = true;
                    $extraRules['monto'] = 'required|numeric|min:0.01';
                    $extraRules['pago_observacion'] = 'nullable|string';
                }
            }
        }

        $rules = array_merge($baseRules, $extraRules);

        // Si se seleccionó 'otro', requerir el campo 'tipo_otro'; si no, validar existencia en la tabla
        $tipoIdInput = $request->input('tipo_id');
        if ($tipoIdInput === 'otro') {
            $rules['tipo_otro'] = 'required|string|max:255';
        } else {
            $rules['tipo_id'] = 'required|exists:sancion_tipos,id';
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Si se eligió 'otro', usar el texto provisto en 'tipo_otro', de lo contrario usar el nombre del tipo
        if ($request->input('tipo_id') === 'otro') {
            $tipoNombre = trim($request->input('tipo_otro')) ?: null;
        } else {
            $tipoNombre = $tipoModel ? $tipoModel->nombre : null;
        }

        $data = [
            'usuario_id' => $request->input('usuario_id'),
            'descripcion' => $request->input('descripcion'),
            'tipo' => $tipoNombre,
            'tipo_id' => $request->input('tipo_id'),
            'fecha' => $request->input('fecha'),
        ];

        if ($isSuspension || $isPrivileges || $isExpulsion) {
            $data['fecha_inicio'] = $request->input('fecha_inicio');
            $data['fecha_fin'] = $request->input('fecha_fin');
        }

        if ($isMonetary) {
            $data['monto'] = $request->input('monto');
            $data['pago_obligatorio'] = true;
            $data['pago_observacion'] = $request->input('pago_observacion') ?? 'Pago obligatorio';
        }

        if ($isMeeting) {
            $data['reunion_at'] = $request->input('reunion_at');
        }

        Sancion::create($data);

        return redirect()->route('gestion-disciplinaria.index')->with('success', 'Sanción registrada correctamente.');
    }
    /**
     * Historial Sanciones.
     */
    public function historialSanciones($id)
    {
        // Evitar que coordinador académico acceda a acciones desde esta vista
        if ($this->isCoordinadorAcademico()) {
            return redirect()->route('gestion-disciplinaria.index')->with('error', 'No tienes permiso para acceder a esta acción.');
        }
        $sanciones = \App\Models\Sancion::with('usuario')->where('usuario_id', $id)->get();
        return view('gestion-disciplinaria.historial_sanciones', compact('sanciones'));
    }

    /**
     * Reporte Sanciones.
     */
    public function generarReporte()
    {
        // Evitar que coordinador académico o docentes usen el reporte desde este módulo
        if ($this->isCoordinadorAcademico() || $this->isDocente()) {
            return redirect()->route('gestion-disciplinaria.index')->with('error', 'No tienes permiso para acceder a esta acción.');
        }
        // Aceptar filtros por query string: start_date, end_date, tipo_id
        $request = request();
        $query = Sancion::with('usuario');

        // Filtrar por rango de fechas (campo 'fecha')
        $start = $request->query('start_date');
        $end = $request->query('end_date');
        if ($start) {
            $startNorm = str_replace('/', '-', $start);
            $query = $query->whereDate('fecha', '>=', $startNorm);
        }
        if ($end) {
            $endNorm = str_replace('/', '-', $end);
            $query = $query->whereDate('fecha', '<=', $endNorm);
        }

        // Filtrar por tipo de sanción (tipo_id)
        $tipoId = $request->query('tipo_id');
        if ($tipoId) {
            $query = $query->where('tipo_id', $tipoId);
        }

        // Si se solicita exportar (CSV), obtenemos todos los resultados y devolvemos un CSV
        $export = $request->query('export');
        $query = $query->orderByDesc('fecha');

        if ($export) {
            $exp = strtolower($export);
            if ($exp === 'csv') {
            $items = $query->get();
            $filename = 'reporte_sanciones_' . now()->format('Ymd_His') . '.csv';

            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ];

            $callback = function() use ($items) {
                $out = fopen('php://output', 'w');
                // encabezados
                fputcsv($out, ['Estudiante', 'Documento', 'Descripción', 'Tipo', 'Fecha']);

                foreach ($items as $s) {
                    $name = optional($s->usuario)->name ?? 'ID_'.$s->usuario_id;
                    $doc = optional($s->usuario)->document_number ?? '';
                    $fecha = $s->fecha ? \Illuminate\Support\Carbon::parse($s->fecha)->format('Y/m/d') : '';
                    fputcsv($out, [$name, $doc, $s->descripcion, $s->tipo, $fecha]);
                }

                fclose($out);
            };

                return response()->streamDownload($callback, $filename, $headers);
            }

            if ($exp === 'excel' || $exp === 'xls') {
                $items = $query->get();
                $filename = 'reporte_sanciones_' . now()->format('Ymd_His') . '.xls';

                $headers = [
                    'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
                    'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                ];

                $callback = function() use ($items) {
                    echo "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"/></head><body>";
                    echo "<table border=\"1\">";
                    echo "<tr><th>Estudiante</th><th>Documento</th><th>Descripción</th><th>Tipo</th><th>Fecha</th></tr>";
                    foreach ($items as $s) {
                        $name = htmlspecialchars(optional($s->usuario)->name ?? 'ID_'.$s->usuario_id, ENT_QUOTES, 'UTF-8');
                        $doc = htmlspecialchars(optional($s->usuario)->document_number ?? '', ENT_QUOTES, 'UTF-8');
                        $desc = htmlspecialchars($s->descripcion ?? '', ENT_QUOTES, 'UTF-8');
                        $tipo = htmlspecialchars($s->tipo ?? '', ENT_QUOTES, 'UTF-8');
                        $fecha = $s->fecha ? \Illuminate\Support\Carbon::parse($s->fecha)->format('Y/m/d') : '';
                        echo "<tr>";
                        echo "<td>{$name}</td><td>{$doc}</td><td>{$desc}</td><td>{$tipo}</td><td>{$fecha}</td>";
                        echo "</tr>";
                    }
                    echo "</table></body></html>";
                };

                return response()->streamDownload($callback, $filename, $headers);
            }

            if ($exp === 'pdf') {
                $items = $query->get();
                $filename = 'reporte_sanciones_' . now()->format('Ymd_His') . '.pdf';

                // Intentar usar barryvdh/laravel-dompdf o Dompdf directamente
                $generatedBy = \Auth::user();
                if (class_exists('\\Barryvdh\\DomPDF\\Facade\\Pdf')) {
                    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('gestion-disciplinaria.reporte_pdf', compact('items', 'generatedBy'));
                    return $pdf->download($filename);
                }

                if (class_exists('\\Dompdf\\Dompdf')) {
                    $html = view('gestion-disciplinaria.reporte_pdf', compact('items', 'generatedBy'))->render();
                    $dompdf = new \Dompdf\Dompdf();
                    $dompdf->loadHtml($html);
                    $dompdf->render();
                    return response($dompdf->output(), 200, [
                        'Content-Type' => 'application/pdf',
                        'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                    ]);
                }

                // No está disponible una librería PDF: indicar al usuario cómo instalar
                return redirect()->back()->with('error', 'Para exportar a PDF instala "barryvdh/laravel-dompdf" (composer require barryvdh/laravel-dompdf)');
            }
        }
        // Paginación por defecto
        $reporte = $query->paginate(20)->withQueryString();

        // Cargar tipos disponibles para el select
        $tipos = \App\Models\SancionTipo::orderBy('nombre')->get();

        return view('gestion-disciplinaria.reporte', compact('reporte', 'tipos'));
    }

    /**
     * Display the specified resource.
     */
    public function index()
    {
        // Si el usuario es docente, redirigirlo a su propio historial de sanciones
        if ($this->isDocente()) {
            return redirect()->route('historial.sanciones', Auth::id());
        }

        $sanciones = Sancion::with('usuario')->get();

        // Pasar flag a la vista para deshabilitar botones si el usuario es coordinador académico
        $isCoordinator = $this->isCoordinadorAcademico();

        return view('gestion-disciplinaria.index', compact('sanciones', 'isCoordinator'));
    }

    /**
     * Buscar estudiante por número de documento y devolver datos (incluido curso matriculado).
     */
    public function buscarPorDocumento(Request $request)
    {
        // Evitar búsqueda por documento si es coordinador académico
        if ($this->isCoordinadorAcademico()) {
            return response()->json(['success' => false, 'message' => 'No tienes permiso para realizar búsquedas.'], 403);
        }
        $document = $request->query('document') ?? $request->input('document');
        if (! $document) {
            return response()->json(['success' => false, 'message' => 'Documento requerido'], 400);
        }

        // Normalizar: extraer solo dígitos del documento para búsquedas más flexibles
        $cleanDigits = preg_replace('/\D+/', '', $document);

        $studentRole = RolesModel::where('nombre', 'Estudiante')->first();

        // Buscamos usando el número normalizado. Usamos REPLACE para ignorar puntos, espacios y guiones
        $query = User::when($studentRole, function ($q) use ($studentRole) {
            return $q->where('roles_id', $studentRole->id);
        });

        if ($cleanDigits !== '') {
            // Buscar donde el número normalizado contiene los dígitos ingresados
            $query = $query->whereRaw("REPLACE(REPLACE(REPLACE(document_number, '.', ''), ' ', ''), '-', '') LIKE ?", ["%{$cleanDigits}%"]);
        } else {
            // Fallback: búsqueda por documento exacto sin normalizar
            $query = $query->where('document_number', $document);
        }

        $user = $query->first();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Estudiante no encontrado'], 404);
        }

        // Obtener la matrícula más reciente (por fecha) junto con el curso
        $matricula = $user->matriculas()->with('curso')->orderByDesc('fecha_matricula')->first();

        // Si el solicitante es docente, permitimos la búsqueda para todos los estudiantes.
        // Dejamos un log informativo para auditoría/debug.
        if ($this->isDocente()) {
            try {
                $docenteId = \Illuminate\Support\Facades\Auth::id();
                $cursoId = $matricula && $matricula->curso ? $matricula->curso->id : null;
                \Log::info('buscarPorDocumento docente acceso permitido', [
                    'docente_id' => $docenteId,
                    'usuario_busqueda_id' => $user->id ?? null,
                    'curso_id' => $cursoId,
                ]);
            } catch (\Throwable $__e) {
                // No interrumpir en caso de fallo de logging
            }
        }

        // Obtener sanciones del estudiante
        $sanciones = \App\Models\Sancion::with('usuario')->where('usuario_id', $user->id)->get();

        return response()->json([
            'success' => true,
            'user' => $user,
            'matricula' => $matricula,
            'sanciones' => $sanciones
        ]);
    }

    /**
     * Determina si el usuario autenticado es 'cordinador academico'.
     */
    private function isCoordinadorAcademico()
    {
        $user = Auth::user();
        if (! $user) return false;

        try {
            $role = RolesModel::find($user->roles_id);
            $roleName = mb_strtolower($role->nombre ?? '');
            // Normalizar acentos para evitar fallos por tildes
            $roleNameNormalized = strtr($roleName, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','Á'=>'a','É'=>'e','Í'=>'i','Ó'=>'o','Ú'=>'u']);
            // Detectar explícitamente variantes de 'Coordinador Académico' únicamente.
            // Evitar detectar otros coordinadores como 'Coordinador Disciplina'.
            if (mb_stripos($roleNameNormalized, 'coordinador academ') !== false || mb_stripos($roleNameNormalized, 'cordinador academ') !== false || mb_stripos($roleNameNormalized, 'coordinador academico') !== false) {
                return true;
            }
        } catch (\Throwable $e) {
            // En caso de error, no bloquear por defecto
            return false;
        }

        return false;
    }

    /**
     * Determina si el usuario autenticado tiene rol 'Docente'.
     */
    private function isDocente()
    {
        $user = Auth::user();
        if (! $user) return false;
        try {
            $role = RolesModel::find($user->roles_id);
            $roleName = mb_strtolower($role->nombre ?? '');
            $roleNameNormalized = strtr($roleName, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','Á'=>'a','É'=>'e','Í'=>'i','Ó'=>'o','Ú'=>'u']);
            if (mb_stripos($roleNameNormalized, 'docente') !== false) return true;
        } catch (\Throwable $e) {
            return false;
        }
        return false;
    }

    /**
     * Endpoint temporal de depuración que comprueba si un docente puede acceder
     * a la información de un estudiante según pivot curso_docente o materias.
     *
     * Query params aceptados: docente_id (opcional, por defecto Auth::id()), usuario_busqueda_id (requerido)
     */
    public function debugCheckAssignment(Request $request)
    {
        $docenteId = $request->query('docente_id') ?? Auth::id();
        $usuarioBusquedaId = $request->query('usuario_busqueda_id');

        if (! $usuarioBusquedaId) {
            return response()->json(['success' => false, 'message' => 'Parámetro usuario_busqueda_id requerido'], 400);
        }

        $user = User::find($usuarioBusquedaId);
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Usuario no encontrado'], 404);
        }

        $matricula = $user->matriculas()->with('curso')->orderByDesc('fecha_matricula')->first();

        $cursoId = null;
        $matriculaId = null;
        $pivotExists = false;
        $materiaExists = false;

        if ($matricula && $matricula->curso) {
            $cursoId = $matricula->curso->id;
            $matriculaId = $matricula->id;
            try {
                $pivotExists = $matricula->curso->docentes()->where('id', $docenteId)->exists();
            } catch (\Throwable $__e) {
                $pivotExists = false;
            }

            try {
                $materiaExists = \App\Models\Materia::where('curso_id', $cursoId)
                    ->where('docente_id', $docenteId)
                    ->exists();
            } catch (\Throwable $__e) {
                $materiaExists = false;
            }
        }

        $pertenece = ($pivotExists || $materiaExists);

        return response()->json([
            'success' => true,
            'docente_id' => (int)$docenteId,
            'usuario_busqueda_id' => (int)$usuarioBusquedaId,
            'curso_id' => $cursoId,
            'matricula_id' => $matriculaId,
            'pivotExists' => (bool)$pivotExists,
            'materiaExists' => (bool)$materiaExists,
            'pertenece' => (bool)$pertenece,
            'matricula' => $matricula ? ['id' => $matricula->id, 'curso_id' => $matricula->curso->id ?? null] : null,
        ]);
    }

}
