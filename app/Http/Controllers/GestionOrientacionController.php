<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cita;
use App\Models\Informe;
use App\Models\Seguimiento;

class GestionOrientacionController extends Controller
{
    // Vista principal del módulo
    public function index()
    {
        return view('orientacion.index');
    }

    // ---------------- Citas ----------------
    public function listarCitas()
    {
        $citas = Cita::all();
        return view('orientacion.citas.index', compact('citas'));
    }

    public function crearCita()
    {
        return view('orientacion.citas.create');
    }

    public function guardarCita(Request $request)
    {
        $request->validate([
            'fecha' => 'required|date',
            'estudiante_id' => 'required|integer'
        ]);

        Cita::create([
            'estudiante_id' => $request->estudiante_id,
            'fecha' => $request->fecha,
            'estado' => 'pendiente'
        ]);

        return redirect()->route('orientacion.citas')->with('success', 'Cita registrada correctamente');
    }

    public function cambiarEstadoCita($id, Request $request)
    {
        $request->validate([
            'estado' => 'required|in:pendiente,agendada,atendida'
        ]);

        $cita = Cita::findOrFail($id);
        $cita->estado = $request->estado;
        $cita->save();

        return back()->with('success', 'Estado de la cita actualizado');
    }

    // ---------------- Informes ----------------
    public function listarInformes()
    {
        $informes = Informe::with('cita')->get();
        return view('orientacion.informes.index', compact('informes'));
    }

    public function crearInforme()
    {
        $citas = Cita::where('estado', 'atendida')->get();
        return view('orientacion.informes.create', compact('citas'));
    }

    public function guardarInforme(Request $request)
    {
        $request->validate([
            'cita_id' => 'required|exists:citas,id',
            'descripcion' => 'required|string'
        ]);

        Informe::create([
            'cita_id' => $request->cita_id,
            'descripcion' => $request->descripcion
        ]);

        return redirect()->route('orientacion.informes')->with('success', 'Informe generado correctamente');
    }

    // ---------------- Seguimientos ----------------
    public function listarSeguimientos()
    {
        $seguimientos = Seguimiento::all();
        return view('orientacion.seguimientos.index', compact('seguimientos'));
    }

    public function crearSeguimiento()
    {
        return view('orientacion.seguimientos.create');
    }

    public function guardarSeguimiento(Request $request)
    {
        $request->validate([
            'estudiante_id' => 'required|integer',
            'observaciones' => 'required|string'
        ]);

        Seguimiento::create([
            'estudiante_id' => $request->estudiante_id,
            'observaciones' => $request->observaciones,
            'fecha' => now()
        ]);

        return redirect()->route('orientacion.seguimientos')->with('success', 'Seguimiento registrado correctamente');
    }
}
