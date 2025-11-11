<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sancion;
use App\Models\User;
use App\Models\RolesModel;

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
                'display' => $s->name . ' (ID: ' . $s->id . ')'
            ];
        })->values()->all();

        return view('gestion-disciplinaria.registrar_sancion', compact('students', 'studentArray'));
    }
    
    /**
     * Registrar Sanción.
     */
    public function registrarSancion(Request $request)
    {
        $request->validate([
            'usuario_id' => 'required|exists:users,id',
            'descripcion' => 'required|string|max:1000',
            'tipo' => 'required|string|max:255',
            'fecha' => 'required|date',
        ]);

        Sancion::create($request->only(['usuario_id','descripcion','tipo','fecha']));

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
        $reporte = Sancion::with('usuario')->get();
        return view('gestion-disciplinaria.reporte', compact('reporte'));
    }

    /**
     * Display the specified resource.
     */
    public function index()
    {
        $sanciones = Sancion::with('usuario')->get();
        return view('gestion-disciplinaria.index', compact('sanciones'));
    }

}
