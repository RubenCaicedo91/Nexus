<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sancion;
use App\Models\User;
use App\Models\RolesModel;
use App\Models\Matricula;

class GestionDisciplinariaController extends Controller
{
    /**
     * Mostrar formulario para crear Sanción.
     */
    public function mostrarFormularioSancion()
    {
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
        $tiposForJs = $tipos->map(function($t){ return ['id' => $t->id, 'nombre' => $t->nombre, 'categoria' => $t->categoria ?? 'normal']; })->values();

        return view('gestion-disciplinaria.registrar_sancion', compact('students', 'studentArray', 'tipos', 'tiposForJs'));
    }
    
    /**
     * Registrar Sanción.
     */
public function registrarSancion(Request $request)
{
    $baseRules = [
        'usuario_id' => 'required|exists:users,id',
        'descripcion' => 'required|string|max:1000',
        'tipo_id' => 'required|exists:sancion_tipos,id',
        'fecha' => 'required|date',
    ];

    // Validación condicional según el tipo seleccionado
    $tipoModel = \App\Models\SancionTipo::find($request->input('tipo_id'));

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

    $validator = \Illuminate\Support\Facades\Validator::make($request->all(), $rules);
    if ($validator->fails()) {
        return redirect()->back()->withErrors($validator)->withInput();
    }

    $tipoNombre = $tipoModel ? $tipoModel->nombre : null;

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
        $sanciones = \App\Models\Sancion::with('usuario')->where('usuario_id', $id)->get();
        return view('gestion-disciplinaria.historial_sanciones', compact('sanciones'));
    }

    /**
     * Reporte Sanciones.
     */
    public function generarReporte()
    {
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
        $sanciones = Sancion::with('usuario')->get();
        return view('gestion-disciplinaria.index', compact('sanciones'));
    }

    /**
     * Buscar estudiante por número de documento y devolver datos (incluido curso matriculado).
     */
    public function buscarPorDocumento(Request $request)
    {
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

        // Obtener sanciones del estudiante
        $sanciones = \App\Models\Sancion::with('usuario')->where('usuario_id', $user->id)->get();

        return response()->json([
            'success' => true,
            'user' => $user,
            'matricula' => $matricula,
            'sanciones' => $sanciones
        ]);
    }

}
