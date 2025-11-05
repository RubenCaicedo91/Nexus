<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sancion;

class GestionDisciplinariaController extends Controller
{
    /**
     * Mostrar formulario para crear Sanción.
     */
    public function mostrarFormularioSancion()
    {
        return view('gestion-disciplinaria.registrar_sancion');
    }
    
    /**
     * Registrar Sanción.
     */
    public function registrarSancion(Request $request)
    {
        Sancion::create([
            'usuario_id' => $request->usuario_id,
            'descripcion' => $request->descripcion,
            'tipo' => $request->tipo,
            'fecha' => $request->fecha,
        ]); 

        return redirect()->route('gestion-disciplinaria.index');
    }
    /**
     * Historial Sanciones.
     */
    public function historialSanciones($id)
    {
        $sanciones = \App\Models\Sancion::where('usuario_id', $id)->get();
        return view('gestion-disciplinaria.historial_sanciones', compact('sanciones'));
    }

    /**
     * Reporte Sanciones.
     */
    public function generarReporte()
    {
        $reporte = Sancion::all();
        return view('gestion-disciplinaria.reporte', compact('reporte'));
    }

    /**
     * Display the specified resource.
     */
    public function index()
    {
        $sanciones = Sancion::all();
        return view('gestion-disciplinaria.index', compact('sanciones'));
    }

}
