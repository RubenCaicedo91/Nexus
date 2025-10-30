<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pago;

class GestionFinancieraController extends Controller
{
    public function mostrarFormularioPago()
    {
        return view('financiera.registrar_pago');
    }

    public function registrarPago(Request $request)
    {
        Pago::create([
            'estudiante_id' => $request->estudiante_id,
            'concepto' => $request->concepto,
            'monto' => $request->monto,
        ]);

        return redirect()->route('estado.cuenta', ['id' => $request->estudiante_id]);
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
