<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Pension;
use App\Models\User;
use App\Models\Curso;
use App\Models\Matricula;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PensionesController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    // Vista principal de pensiones
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Pension::with(['estudiante', 'acudiente', 'curso', 'procesadoPor']);

        // Filtros según el rol del usuario
        if ($user->roles_id == 4) { // Acudiente
            $query->where('acudiente_id', $user->id);
        }

        // Aplicar filtros de búsqueda
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('mes')) {
            $query->where('mes_correspondiente', $request->mes);
        }

        if ($request->filled('mes')) {
            $query->where('mes_correspondiente', $request->mes);
        }

        if ($request->filled('año')) {
            $query->where('año_correspondiente', $request->año);
        } else {
            $query->where('año_correspondiente', date('Y'));
        }

        if ($request->filled('grado')) {
            $query->where('grado', $request->grado);
        }

        // Ordenar por fecha de vencimiento
        $pensiones = $query->orderBy('fecha_vencimiento', 'desc')->paginate(20);

        // Datos para filtros
        $estudiantes = collect();
        $grados = collect();
    $cursos = collect();

        if ($user->roles_id != 4) { // Si no es acudiente
            $estudiantes = User::where('roles_id', 3)->get(); // Estudiantes
            $grados = Curso::distinct()->pluck('grado');
            $cursos = Curso::orderBy('nombre')->get();
        } else {
            // Solo estudiantes del acudiente
            $estudiantes = User::whereHas('matriculas', function($q) use ($user) {
                $q->where('acudiente_id', $user->id);
            })->get();
        }

    return view('pensiones.index', compact('pensiones', 'estudiantes', 'grados', 'cursos'));
    }

    // Mostrar formulario de creación de pensión
    public function create()
    {
        $estudiantes = User::where('roles_id', 3)->get(); // Estudiantes
        $cursos = Curso::all();

        // Preparar arreglo de datos que se injertará como JSON en la vista
        $estudiantesDataArr = [];
        foreach ($estudiantes as $estudiante) {
            $matricula = $estudiante->matriculas->where('estado', 'activa')->first();
            $estudiantesDataArr[$estudiante->id] = [
                'id' => $estudiante->id,
                'nombre' => $estudiante->name,
                'email' => $estudiante->email,
                'matricula' => $matricula ? [
                    'curso_id' => $matricula->curso_id,
                    'curso_nombre' => $matricula->curso->nombre ?? 'N/A',
                    'grado' => $matricula->curso->grado ?? 'N/A',
                    'acudiente' => $matricula->acudiente->name ?? 'N/A'
                ] : null
            ];
        }

        return view('pensiones.create', compact('estudiantes', 'cursos', 'estudiantesDataArr'));
    }

    // Guardar nueva pensión
    public function store(Request $request)
    {
        $request->validate([
            'estudiante_id' => 'required|exists:users,id',
            'mes' => 'required|integer|between:1,12',
            'año' => 'required|integer|min:2024',
            'concepto' => 'required|string|max:255',
            'valor_base' => 'required|numeric|min:0',
            'descuentos' => 'nullable|numeric|min:0',
            'recargos' => 'nullable|numeric|min:0',
            'fecha_vencimiento' => 'required|date',
            'observaciones' => 'nullable|string|max:1000'
        ]);

        // Obtener datos del estudiante
        $estudiante = User::find($request->estudiante_id);
        $matricula = Matricula::where('estudiante_id', $estudiante->id)
                             ->where('estado', 'activa')
                             ->first();

        if (!$matricula) {
            return back()->withErrors(['estudiante_id' => 'El estudiante no tiene matrícula activa.']);
        }

    // Verificar si ya existe pensión para ese mes/año (usar columnas canonicas)
    $pensionExistente = Pension::where('estudiante_id', $request->estudiante_id)
                  ->where('mes_correspondiente', $request->mes)
                  ->where('año_correspondiente', $request->año)
                  ->where('concepto', $request->concepto)
                  ->first();

        if ($pensionExistente) {
            return back()->withErrors(['mes' => 'Ya existe una pensión para este estudiante en el mes/año seleccionado.']);
        }

        $pension = new Pension([
            'estudiante_id' => $request->estudiante_id,
            'acudiente_id' => $matricula->acudiente_id,
            'curso_id' => $matricula->curso_id,
            'grado' => $matricula->curso->grado ?? '',
            'concepto' => $request->concepto,
            'mes_correspondiente' => $request->mes,
            'año_correspondiente' => $request->año,
            'valor_base' => $request->valor_base,
            'descuentos' => $request->descuentos ?? 0,
            'recargos' => $request->recargos ?? 0,
            'fecha_vencimiento' => $request->fecha_vencimiento,
            'estado' => Pension::ESTADO_PENDIENTE,
            'observaciones' => $request->observaciones
        ]);

        $pension->save();

        return redirect()->route('pensiones.index')
                        ->with('success', 'Pensión creada exitosamente.');
    }

    // Mostrar detalle de pensión
    public function show(Pension $pension)
    {
        $pension->load(['estudiante', 'acudiente', 'curso', 'procesadoPor']);
        
        // Verificar permisos
        $user = Auth::user();
        if ($user->roles_id == 4 && $pension->acudiente_id != $user->id) {
            abort(403, 'No tienes permiso para ver esta pensión.');
        }

        return view('pensiones.show', compact('pension'));
    }

    // Mostrar formulario de edición
    public function edit(Pension $pension)
    {
        // Solo administradores pueden editar
        if (Auth::user()->roles_id == 4) {
            abort(403, 'No tienes permiso para editar pensiones.');
        }

    $estudiantes = User::where('roles_id', 3)->get();
        $cursos = Curso::all();
        
        return view('pensiones.edit', compact('pension', 'estudiantes', 'cursos'));
    }

    // Actualizar pensión
    public function update(Request $request, Pension $pension)
    {
        // Solo administradores pueden actualizar
        if (Auth::user()->roles_id == 4) {
            abort(403, 'No tienes permiso para editar pensiones.');
        }

        $request->validate([
            'concepto' => 'required|string|max:255',
            'valor_base' => 'required|numeric|min:0',
            'descuentos' => 'nullable|numeric|min:0',
            'recargos' => 'nullable|numeric|min:0',
            'fecha_vencimiento' => 'required|date',
            'observaciones' => 'nullable|string|max:1000'
        ]);

        $pension->update([
            'concepto' => $request->concepto,
            'valor_base' => $request->valor_base,
            'descuentos' => $request->descuentos ?? 0,
            'recargos' => $request->recargos ?? 0,
            'fecha_vencimiento' => $request->fecha_vencimiento,
            'observaciones' => $request->observaciones
        ]);

        return redirect()->route('pensiones.index')
                        ->with('success', 'Pensión actualizada exitosamente.');
    }

    // Procesar pago
    public function procesarPago(Request $request, Pension $pension)
    {
        $request->validate([
            'metodo_pago' => 'required|in:efectivo,transferencia,tarjeta,consignacion,pse',
            'numero_recibo' => 'required|string|max:50',
            'comprobante_pago' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB
            'observaciones_pago' => 'nullable|string|max:500'
        ]);

        // Actualizar recargo por mora si es necesario
        $pension->actualizarRecargo();

        // Procesar archivo de comprobante
        $comprobantePath = null;
        if ($request->hasFile('comprobante_pago')) {
            $comprobantePath = $request->file('comprobante_pago')
                                     ->store('comprobantes-pago', 'public');
        }

        // Marcar como pagada
        $pension->marcarComoPagada(
            $request->metodo_pago,
            $request->numero_recibo,
            Auth::id()
        );

        if ($comprobantePath) {
            $pension->comprobante_pago = $comprobantePath;
        }

        if ($request->filled('observaciones_pago')) {
            $pension->observaciones = ($pension->observaciones ? $pension->observaciones . "\n\n" : '') . 
                                    "PAGO: " . $request->observaciones_pago;
        }

        $pension->save();

        return redirect()->route('pensiones.show', $pension)
                        ->with('success', 'Pago procesado exitosamente.');
    }

    // Anular pensión
    public function anular(Request $request, Pension $pension)
    {
        // Solo administradores pueden anular
        if (Auth::user()->role_id == 4) {
            abort(403, 'No tienes permiso para anular pensiones.');
        }

        $request->validate([
            'motivo_anulacion' => 'required|string|max:500'
        ]);

        $pension->anular("ANULADA: " . $request->motivo_anulacion . " (Por: " . Auth::user()->name . ")");

        return redirect()->route('pensiones.index')
                        ->with('success', 'Pensión anulada exitosamente.');
    }

    // Generar pensiones masivas para un curso/grado
    public function generarMasivas(Request $request)
    {
        // Solo administradores
        if (Auth::user()->roles_id == 4) {
            abort(403, 'No tienes permiso para generar pensiones masivas.');
        }

        $request->validate([
            'curso_id' => 'nullable|exists:cursos,id',
            'grado' => 'nullable|string',
            'mes' => 'required|integer|between:1,12',
            'año' => 'required|integer|min:2024',
            'concepto' => 'required|string|max:255',
            'valor_base' => 'required|numeric|min:0',
            'fecha_vencimiento' => 'required|date',
            'descuentos' => 'nullable|numeric|min:0',
            'recargos' => 'nullable|numeric|min:0'
        ]);

        // Obtener estudiantes activos
        $query = Matricula::where('estado', 'activa')
                          ->with(['estudiante', 'curso']);

        if ($request->filled('curso_id')) {
            $query->where('curso_id', $request->curso_id);
        }

        if ($request->filled('grado')) {
            $query->whereHas('curso', function($q) use ($request) {
                $q->where('grado', $request->grado);
            });
        }

        $matriculas = $query->get();
        $pensionesCreadas = 0;
        $errores = [];

        foreach ($matriculas as $matricula) {
            // Verificar si ya existe
            $existente = Pension::where('estudiante_id', $matricula->estudiante_id)
                               ->where('mes_correspondiente', $request->mes)
                               ->where('año_correspondiente', $request->año)
                               ->where('concepto', $request->concepto)
                               ->first();

            if ($existente) {
                $errores[] = "Ya existe pensión para {$matricula->estudiante->name} en {$request->mes}/{$request->año}";
                continue;
            }

            try {
                $pension = new Pension([
                    'estudiante_id' => $matricula->estudiante_id,
                    'acudiente_id' => $matricula->acudiente_id,
                    'curso_id' => $matricula->curso_id,
                    'grado' => $matricula->curso->grado ?? '',
                    'concepto' => $request->concepto,
                    'mes_correspondiente' => $request->mes,
                    'año_correspondiente' => $request->año,
                    'valor_base' => $request->valor_base,
                    'descuentos' => $request->descuentos ?? 0,
                    'recargos' => $request->recargos ?? 0,
                    'fecha_vencimiento' => $request->fecha_vencimiento,
                    'estado' => Pension::ESTADO_PENDIENTE
                ]);

                $pension->save();
                $pensionesCreadas++;
            } catch (\Exception $e) {
                $errores[] = "Error creando pensión para {$matricula->estudiante->name}: {$e->getMessage()}";
            }
        }

        $mensaje = "Se crearon {$pensionesCreadas} pensiones exitosamente.";
        if (!empty($errores)) {
            $mensaje .= " Errores: " . implode(", ", array_slice($errores, 0, 3));
        }

        return redirect()->route('pensiones.index')
                        ->with('success', $mensaje);
    }

    // Reporte de pensiones
    public function reporte(Request $request)
    {
        $query = Pension::with(['estudiante', 'acudiente', 'curso']);

        // Filtros
        if ($request->filled('mes')) {
            $query->where('mes_correspondiente', $request->mes);
        }

        if ($request->filled('año')) {
            $query->where('año_correspondiente', $request->año);
        } else {
            $query->where('año_correspondiente', date('Y'));
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('grado')) {
            $query->where('grado', $request->grado);
        }

        $pensiones = $query->get();

        // Estadísticas
        $estadisticas = [
            'total_pensiones' => $pensiones->count(),
            'pendientes' => $pensiones->where('estado', Pension::ESTADO_PENDIENTE)->count(),
            'pagadas' => $pensiones->where('estado', Pension::ESTADO_PAGADA)->count(),
            'vencidas' => $pensiones->where('estado', Pension::ESTADO_VENCIDA)->count(),
            'valor_total' => $pensiones->sum('valor_total'),
            'valor_pendiente' => $pensiones->whereIn('estado', [Pension::ESTADO_PENDIENTE, Pension::ESTADO_VENCIDA])->sum('valor_total'),
            'valor_recaudado' => $pensiones->where('estado', Pension::ESTADO_PAGADA)->sum('valor_total')
        ];

        return view('pensiones.reporte', compact('pensiones', 'estadisticas'));
    }

    // Actualizar estados vencidos (comando automático)
    public function actualizarVencidas()
    {
        $pensionesVencidas = Pension::where('estado', Pension::ESTADO_PENDIENTE)
                                  ->where('fecha_vencimiento', '<', Carbon::now())
                                  ->get();

        foreach ($pensionesVencidas as $pension) {
            $pension->estado = Pension::ESTADO_VENCIDA;
            $pension->actualizarRecargo();
        }

        return response()->json([
            'message' => 'Estados actualizados',
            'actualizadas' => $pensionesVencidas->count()
        ]);
    }
}
