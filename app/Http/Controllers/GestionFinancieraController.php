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

class GestionFinancieraController extends Controller
{
    public function mostrarFormularioPago()
    {
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
        $validated = $request->validate([
            'estudiante_id' => ['required','integer'],
            'concepto' => ['required','string'],
            'monto' => ['required','numeric','min:0'],
            'faltante' => ['nullable','numeric','min:0'],
        ]);

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
                $matricula->monto_pago = $validated['monto'];
                $matricula->fecha_pago = Carbon::now();

                $faltante = isset($validated['faltante']) ? floatval($validated['faltante']) : max(0, $valorMatricula - floatval($validated['monto']));

                if ($faltante <= 0) {
                    $matricula->pago_validado = true;
                    $matricula->pago_validado_por = Auth::id();
                    $matricula->pago_validado_at = Carbon::now();
                } else {
                    $matricula->pago_validado = false;
                }

                $matricula->save();
            }
        }

        return redirect()->route('financiera.estadoCuenta', ['id' => $validated['estudiante_id']])->with('success', 'Pago registrado correctamente.');
    }

    public function estadoCuenta($id)
    {
        $pagos = Pago::where('estudiante_id', $id)->get();
        return view('financiera.estado_cuenta', compact('pagos'));
    }

    public function generarReporte()
    {
        $reporte = Pago::all();
        return view('financiera.reporte', compact('reporte'));
    }

    public function index()
    {
        return view('financiera.index');
    }
    //
}
