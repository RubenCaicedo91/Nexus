<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pago;
use App\Models\Matricula;
use App\Models\Institucion;
use App\Models\MatriculaComprobante;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\QueryException;
use Carbon\Carbon;
use Dompdf\Dompdf;
use App\Models\Notificacion;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class GestionFinancieraController extends Controller
{
    public function mostrarFormularioPago()
    {
        // Si el usuario es coordinador académico, sólo puede consultar estado; prohibimos abrir formulario de registro
        if ($this->isCoordinadorAcademico()) {
            abort(403, 'No tienes permiso para registrar pagos.');
        }
        // Obtener matrículas que tienen algún comprobante o registro de pago
        // pero aún no han sido validadas (pago_validado = false/null)
        $pendientes = Matricula::with(['user', 'curso'])
            ->where(function ($q) {
                $q->whereNotNull('comprobante_pago')
                  ->orWhere(function ($q2) {
                      $q2->whereNotNull('monto_pago')->whereNotNull('fecha_pago');
                  });
            })
            ->where(function ($q) {
                $q->where('pago_validado', false)
                  ->orWhere('pago_validado', 0)
                  ->orWhereNull('pago_validado');
            })
            ->get();

        // Valor estándar de la matrícula (precedencia: tabla 'institucion' -> config)
        $institucion = Institucion::first();
        $valorMatricula = $institucion && $institucion->valor_matricula ? $institucion->valor_matricula : config('financiera.valor_matricula', 0);

        // Si el usuario actual es tesorero/administrador, adjuntar comprobantes históricos
        $user = Auth::user();
        $roleNombre = optional($user)->role->nombre ?? '';
        $isPrivileged = false;
        if ($user && (stripos($roleNombre, 'tesor') !== false || stripos($roleNombre, 'administrador') !== false || stripos($roleNombre, 'admin') !== false)) {
            $isPrivileged = true;
        }

        if ($isPrivileged) {
            foreach ($pendientes as $p) {
                try {
                    $comps = MatriculaComprobante::where('matricula_id', $p->id)->orderBy('created_at', 'desc')->get();

                    // Si no hay entradas en la tabla de auditoría, hacer fallback a listar archivos en el disco FTP
                    if ($comps->isEmpty()) {
                        $found = collect();
                        try {
                            $disk = Storage::disk('ftp_matriculas');
                            $docNumber = optional($p->user)->document_number ?? null;
                            $docSegment = $docNumber ? preg_replace('/[^A-Za-z0-9_\-]/', '_', trim((string)$docNumber)) : ('id_' . ($p->user_id ?? $p->id));
                            $base = 'PM_' . $docSegment;

                            $all = [];
                            try { $all = $disk->allFiles(''); } catch (\Exception $e) { $all = []; }
                            try { $all = array_merge($all, $disk->allFiles('estudiante')); } catch (\Exception $e) { /* ignore */ }

                            foreach ($all as $candidate) {
                                if (stripos(basename($candidate), $base) === 0) {
                                    $found->push((object)[
                                        'filename' => basename($candidate),
                                        'path' => $candidate,
                                        'created_at' => null,
                                    ]);
                                }
                            }
                        } catch (\Exception $e) {
                            $found = collect();
                        }

                        $p->comprobantes = $found;
                    } else {
                        $p->comprobantes = $comps;
                    }
                } catch (\Exception $e) {
                    $p->comprobantes = collect();
                }
            }
        }

        return view('financiera.registrar_pago', compact('pendientes', 'valorMatricula', 'isPrivileged'));
    }

    /**
     * Actualizar el valor de la matrícula (solo permitido para tesorero/administrador)
     */
    public function actualizarValorMatricula(Request $request)
    {
        $user = Auth::user();
        if ($this->isCoordinadorAcademico()) {
            abort(403, 'No tienes permiso para esta acción.');
        }
        if (! $user) {
            abort(403, 'Acceso no autorizado');
        }

        $allowed = ['Tesorero', 'tesorero', 'Administrador_sistema', 'Administrador de sistema', 'Administrador'];
        $roleNombre = optional($user->role)->nombre ?? '';
        $isAllowed = false;
        foreach ($allowed as $a) {
            if ($roleNombre === $a || stripos($roleNombre, $a) !== false) {
                $isAllowed = true;
                break;
            }
        }

        if (! $isAllowed) {
            abort(403, 'Acceso no autorizado');
        }

        $validated = $request->validate([
            'valor_matricula' => ['required','numeric','min:0'],
        ]);

        $institucion = Institucion::first();
        if (! $institucion) {
            $institucion = Institucion::create(['nombre' => 'Institución']);
        }

        $institucion->valor_matricula = $validated['valor_matricula'];

        try {
            $institucion->save();
        } catch (QueryException $e) {
            // Si falla por columna inexistente, intentamos crearla y reintentar.
            $msg = $e->getMessage();
            if (stripos($msg, 'Unknown column') !== false || stripos($msg, 'valor_matricula') !== false) {
                if (! Schema::hasColumn('institucion', 'valor_matricula')) {
                    Schema::table('institucion', function (Blueprint $table) {
                        $table->decimal('valor_matricula', 12, 2)->nullable()->default(0);
                    });
                }

                // reintentar guardar
                $institucion->save();
            } else {
                // si es otra excepción, volver a lanzar
                throw $e;
            }
        }

        return redirect()->back()->with('success', 'Valor de matrícula actualizado correctamente.');
    }

    public function registrarPago(Request $request)
    {
        // Authorization: only users with explicit permission 'registrar_pagos',
        // or roles like tesorero/administrador (or super admin roles_id==1) may register payments.
        $user = Auth::user();
        // Si es coordinador academico no puede registrar pagos
        if ($this->isCoordinadorAcademico()) {
            abort(403, 'No tienes permiso para registrar pagos.');
        }
        $roleNombre = optional($user->role)->nombre ?? '';
        $isAllowed = false;
        if ($user) {
            if (method_exists($user, 'hasPermission') && $user->hasPermission('registrar_pagos')) {
                $isAllowed = true;
            }
            if (stripos($roleNombre, 'tesor') !== false || stripos($roleNombre, 'administrador') !== false || stripos($roleNombre, 'admin') !== false) {
                $isAllowed = true;
            }
            if ($user->roles_id == 1) {
                $isAllowed = true;
            }
        }

        if (! $isAllowed) {
            abort(403, 'No tienes permiso para registrar pagos.');
        }

        $validated = $request->validate([
            'estudiante_id' => ['required','integer'],
            'concepto' => ['required','string'],
            'monto' => ['required','numeric','min:0'],
            'tipo_pago' => ['nullable','string'], // 'incompleto'|'completo'
            'faltante' => ['nullable','numeric','min:0'],
        ]);

        // Antes de crear el pago, validar en el servidor que no supere el faltante (si es matrícula)
        if ($validated['concepto'] === 'matricula') {
            $estudianteId = $validated['estudiante_id'];

            // Obtener valor de matrícula (institución o config)
            $institucion = Institucion::first();
            $valorMatricula = $institucion && $institucion->valor_matricula ? $institucion->valor_matricula : config('financiera.valor_matricula', 0);

            // Obtener monto ya pagado (preferimos tomarlo desde la matrícula si existe)
            $matricula = Matricula::where('user_id', $estudianteId)->orderByDesc('fecha_matricula')->first();
            $prevPaid = 0;
            if ($matricula) {
                $prevPaid = floatval($matricula->monto_pago ?? 0);
            } else {
                // fallback: sumar pagos ya registrados
                $prevPaid = floatval(Pago::where('estudiante_id', $estudianteId)->sum('monto'));
            }

            $remaining = max(0, floatval($valorMatricula) - $prevPaid);

            if (floatval($validated['monto']) > $remaining) {
                return redirect()->back()
                    ->withErrors(['monto' => 'El monto ingresado supera el faltante disponible (' . number_format($remaining, 2, ',', '.') . ').'])
                    ->withInput();
            }
        }

        $pago = Pago::create([
            'estudiante_id' => $validated['estudiante_id'],
            'concepto' => $validated['concepto'],
            'monto' => $validated['monto'],
        ]);

        // Si el pago es de tipo matrícula, actualizar la matrícula del estudiante
        if ($validated['concepto'] === 'matricula') {
            $estudianteId = $validated['estudiante_id'];
            $matricula = Matricula::where('user_id', $estudianteId)->orderByDesc('fecha_matricula')->first();

            // Obtener valor de matrícula (institución o config)
            $institucion = Institucion::first();
            $valorMatricula = $institucion && $institucion->valor_matricula ? $institucion->valor_matricula : config('financiera.valor_matricula', 0);

            if ($matricula) {
                // Acumular el monto pagado (varias transacciones suman)
                $prev = floatval($matricula->monto_pago ?? 0);
                $added = floatval($validated['monto']);
                $totalPagado = $prev + $added;

                $matricula->monto_pago = $totalPagado;
                $matricula->fecha_pago = Carbon::now();

                // Calcular faltante real según el valor de la institución
                $faltante = max(0, floatval($valorMatricula) - $totalPagado);

                // Si el usuario tiene rol tesorero/administrador puede forzar el tipo de pago
                $user = Auth::user();
                $roleNombre = optional($user->role)->nombre ?? '';
                $canSetTipo = ($user && (stripos($roleNombre, 'tesor') !== false || stripos($roleNombre, 'administrador') !== false || stripos($roleNombre, 'admin') !== false));

                $tipoPago = $validated['tipo_pago'] ?? null;

                if ($canSetTipo && $tipoPago) {
                    if (strtolower($tipoPago) === 'incompleto') {
                        $matricula->pago_validado = false;
                        $matricula->estado = 'pago por cuotas';
                    } elseif (strtolower($tipoPago) === 'completo') {
                        $matricula->pago_validado = true;
                        $matricula->pago_validado_por = Auth::id();
                        $matricula->pago_validado_at = Carbon::now();
                        $matricula->estado = 'pago_validado';
                    }
                } else {
                    // comportamiento automático según monto
                    if ($faltante <= 0) {
                        $matricula->pago_validado = true;
                        $matricula->pago_validado_por = Auth::id();
                        $matricula->pago_validado_at = Carbon::now();
                        $matricula->estado = 'pago_validado';
                    } else {
                        $matricula->pago_validado = false;
                        // si no está validado y hay algún pago, marcar como pago por cuotas
                        if ($totalPagado > 0) {
                            $matricula->estado = 'pago por cuotas';
                        }
                    }
                }

                $matricula->save();

                // Si quedó totalmente pagada, crear notificación al estudiante
                if ($faltante <= 0) {
                    try {
                        // Crear notificación simple en la tabla notificaciones si existe
                            if (class_exists(Notificacion::class)) {
                                    try {
                                        // Preferir notificar al acudiente registrado del estudiante
                                        $studentUser = User::find($matricula->user_id);
                                        $destUserId = $studentUser && $studentUser->acudiente_id ? $studentUser->acudiente_id : $matricula->user_id;

                                        Notificacion::create([
                                            'usuario_id' => $destUserId,
                                            'titulo' => 'Pago de matrícula completado',
                                            'mensaje' => 'La matrícula del estudiante ha sido registrada como pagada en su totalidad el ' . Carbon::now()->format('Y-m-d H:i') . '.',
                                            'leida' => false,
                                            'fecha' => Carbon::now(),
                                            'solo_lectura' => true,
                                            'tipo' => 'pago_matricula',
                                        ]);
                                    } catch (\Throwable $e) {
                                        Log::warning('registrarPago: no se pudo crear notificación al acudiente', ['error' => $e->getMessage()]);
                                    }
                                }
                    } catch (\Exception $e) {
                        // No bloquear el flujo si falla la notificación
                        Log::warning('registrarPago: no se pudo crear notificación', ['error' => $e->getMessage()]);
                    }
                }
            }
        }

        $msg = 'Pago registrado correctamente.';
        // si fue matricula y la matricula quedó validada, añadir mensaje
        if (($validated['concepto'] === 'matricula') && isset($matricula) && $matricula->pago_validado) {
            $msg .= ' La matrícula ha sido pagada en su totalidad y marcada como validada.';
        } elseif ($validated['concepto'] === 'matricula' && isset($matricula)) {
            $msg .= ' Faltante: ' . number_format($faltante, 2, ',', '.');
        }

        return redirect()->route('financiera.estadoCuenta', ['id' => $validated['estudiante_id']])->with('success', $msg);
    }

    public function estadoCuenta($id)
    {
        $estudiante = User::find($id);
        $pagos = Pago::where('estudiante_id', $id)->get();

        $institucion = Institucion::first();
        $valorMatricula = $institucion && $institucion->valor_matricula ? $institucion->valor_matricula : config('financiera.valor_matricula', 0);

        $montoPagado = floatval($pagos->sum('monto'));
        $matricula = Matricula::where('user_id', $id)->orderByDesc('fecha_matricula')->first();
        $faltante = max(0, floatval($valorMatricula) - $montoPagado);

        $searched = true; // acceso por id se considera como búsqueda/consulta
        $isCoordinator = $this->isCoordinadorAcademico();
        return view('financiera.estado_cuenta', compact('pagos', 'estudiante', 'matricula', 'montoPagado', 'faltante', 'valorMatricula', 'searched', 'isCoordinator'));
    }

    /**
     * Buscar estado de cuenta por número de documento (GET ?documento=...)
     */
    public function estadoCuentaSearch(Request $request)
    {
        $documento = $request->query('documento');
        $estudiante = null;
        $pagos = collect();
        $matricula = null;
        $montoPagado = 0;
        $faltante = null;

        $institucion = Institucion::first();
        $valorMatricula = $institucion && $institucion->valor_matricula ? $institucion->valor_matricula : config('financiera.valor_matricula', 0);

        if ($documento) {
            $estudiante = User::where('document_number', $documento)
                ->orWhere('document_number', 'like', $documento . '%')
                ->first();

            if ($estudiante) {
                $pagos = Pago::where('estudiante_id', $estudiante->id)->get();
                $montoPagado = floatval($pagos->sum('monto'));
                $matricula = Matricula::where('user_id', $estudiante->id)->orderByDesc('fecha_matricula')->first();
                $faltante = max(0, floatval($valorMatricula) - $montoPagado);
            }
        }

        $searched = !empty($documento);
        $isCoordinator = $this->isCoordinadorAcademico();
        return view('financiera.estado_cuenta', compact('pagos', 'estudiante', 'matricula', 'montoPagado', 'faltante', 'valorMatricula', 'documento', 'searched', 'isCoordinator'));
    }

    public function generarReporte(Request $request)
    {
        // Si el usuario es coordinador académico, no permitir generar reportes (solo consulta de estado)
        if ($this->isCoordinadorAcademico()) {
            abort(403, 'No tienes permiso para generar reportes financieros.');
        }
        $query = Pago::with(['estudiante.matriculas.curso']);

        // filtro por curso (se busca en las matrículas del estudiante)
        $cursoId = $request->query('curso_id');
        if ($cursoId) {
            $query->whereHas('estudiante.matriculas', function ($q) use ($cursoId) {
                $q->where('curso_id', $cursoId);
            });
        }

        // filtro por estado (se busca en las matrículas del estudiante)
        $estadoFiltro = $request->query('estado');
        if ($estadoFiltro) {
            $query->whereHas('estudiante.matriculas', function ($q) use ($estadoFiltro) {
                $q->where('estado', $estadoFiltro);
            });
        }

        // filtro por concepto
        $concepto = $request->query('concepto');
        if ($concepto) {
            $query->where('concepto', $concepto);
        }

        // Export options: if export=pdf or excel, get full collection
        $export = $request->query('export');

        if ($export === 'pdf' || $export === 'excel') {
            $reporte = $query->orderByDesc('created_at')->get();
        } else {
            $reporte = $query->orderByDesc('created_at')->paginate(25)->withQueryString();
        }

        // lista de conceptos para el filtro
        $conceptos = Pago::select('concepto')->distinct()->pluck('concepto');
        // lista de cursos y estados para los filtros (tomados de la tabla cursos y matrículas)
        try {
            $cursos = \App\Models\Curso::orderBy('nombre')->get();
        } catch (\Throwable $e) {
            $cursos = collect();
        }

        try {
            $estados = \App\Models\Matricula::select('estado')->distinct()->pluck('estado')->filter()->values();
        } catch (\Throwable $e) {
            $estados = collect();
        }

        if ($export === 'pdf') {
            // Renderizar la vista a HTML
            $html = view('financiera.reporte_pdf', compact('reporte'))->render();

            try {
                $dompdf = new Dompdf();
                $dompdf->loadHtml($html);
                // Opcional: establecer tamaño y orientación
                $dompdf->setPaper('A4', 'landscape');
                $dompdf->render();
                $output = $dompdf->output();

                $filename = 'reporte_financiero_' . date('Ymd_His') . '.pdf';

                return response($output, 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                ]);
            } catch (\Throwable $e) {
                // Si falla la generación del PDF, caer de vuelta al view HTML para depurar
                \Log::error('generarReporte: error generando PDF', ['error' => $e->getMessage()]);
                return view('financiera.reporte_pdf', compact('reporte'));
            }
        }

        if ($export === 'excel') {
            // Export to a format Excel can open: HTML table with Excel content-type.
            $rows = $reporte->map(function ($p) {
                $mat = null;
                if ($p->estudiante && $p->estudiante->matriculas) {
                    $mat = $p->estudiante->matriculas->sortByDesc('fecha_matricula')->first();
                }
                $curso = $mat && $mat->curso ? $mat->curso->nombre : '';
                $estado = $mat ? ($mat->estado ?? '') : null;
                $tieneMatricula = $mat ? true : false;

                return [
                    'Estudiante' => optional($p->estudiante)->name ?? $p->estudiante_id,
                    'Concepto' => ucfirst($p->concepto),
                    'Monto' => number_format($p->monto, 0, ',', '.'),
                    'Tiene Matrícula' => $tieneMatricula ? 'Sí' : 'No',
                    'Curso' => $curso ?: '-',
                    'Estado Pago' => $estado ? $estado : ($tieneMatricula ? '' : 'Sin matrícula'),
                ];
            })->toArray();

            $filename = 'reporte_financiero_' . date('Ymd_His') . '.xls';

            // Build an HTML table (Excel can open it) to preserve column order and formatting.
            $html = '<table border="1"><thead><tr>';
            if (!empty($rows)) {
                foreach (array_keys($rows[0]) as $col) {
                    $html .= '<th>' . htmlentities($col) . '</th>';
                }
            }
            $html .= '</tr></thead><tbody>';
            foreach ($rows as $r) {
                $html .= '<tr>';
                foreach ($r as $cell) {
                    $html .= '<td>' . htmlentities($cell) . '</td>';
                }
                $html .= '</tr>';
            }
            $html .= '</tbody></table>';

            return response($html, 200, [
                'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ]);
        }

        return view('financiera.reporte', compact('reporte', 'conceptos', 'cursos', 'estados'));
    }

    /**
     * Determina si el usuario autenticado es coordinador académico.
     */
    private function isCoordinadorAcademico()
    {
        try {
            $user = Auth::user();
            if (! $user) return false;
            $role = optional($user->role)->nombre ?? '';
            $roleName = mb_strtolower($role);
            $roleName = strtr($roleName, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','Á'=>'a','É'=>'e','Í'=>'i','Ó'=>'o','Ú'=>'u']);
            if (mb_stripos($roleName, 'coordinador') !== false || mb_stripos($roleName, 'cordinador') !== false) {
                return true;
            }
        } catch (\Throwable $e) {
            return false;
        }

        return false;
    }

    public function index()
    {
        $isCoordinator = false;
        try {
            $user = Auth::user();
            if ($user) {
                $role = optional($user->role)->nombre ?? '';
                $roleNorm = mb_strtolower($role);
                $roleNorm = strtr($roleNorm, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','Á'=>'a','É'=>'e','Í'=>'i','Ó'=>'o','Ú'=>'u']);
                if (mb_stripos($roleNorm, 'coordinador') !== false || mb_stripos($roleNorm, 'cordinador') !== false) {
                    $isCoordinator = true;
                }
            }
        } catch (\Throwable $e) {
            $isCoordinator = false;
        }

        return view('financiera.index', compact('isCoordinator'));
    }
    //
}

