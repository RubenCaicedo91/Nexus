<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SancionTipo;

class SancionTipoController extends Controller
{
    public function index()
    {
        $tipos = SancionTipo::orderBy('nombre')->get();
        return view('sancion_tipos.index', compact('tipos'));
    }

    public function create()
    {
        return view('sancion_tipos.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:255|unique:sancion_tipos,nombre',
            'descripcion' => 'nullable|string',
            'categoria' => 'nullable|string|max:50',
            'severidad' => 'nullable|string|max:50',
            'duracion_defecto_dias' => 'nullable|integer',
            'activo' => 'nullable|boolean',
        ]);

        $data['activo'] = $request->has('activo') ? (bool)$request->activo : true;

        SancionTipo::create($data);
        return redirect()->route('gestion-disciplinaria.tipos.index')->with('success', 'Tipo creado correctamente.');
    }

    public function edit(SancionTipo $tipo)
    {
        return view('sancion_tipos.edit', ['tipo' => $tipo]);
    }

    public function update(Request $request, SancionTipo $tipo)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:255|unique:sancion_tipos,nombre,' . $tipo->id,
            'descripcion' => 'nullable|string',
            'categoria' => 'nullable|string|max:50',
            'severidad' => 'nullable|string|max:50',
            'duracion_defecto_dias' => 'nullable|integer',
            'activo' => 'nullable|boolean',
        ]);

        $data['activo'] = $request->has('activo') ? (bool)$request->activo : false;

        $tipo->update($data);
        return redirect()->route('gestion-disciplinaria.tipos.index')->with('success', 'Tipo actualizado correctamente.');
    }

    public function destroy(SancionTipo $tipo)
    {
        // Borrar físicamente; las sanciones referenciadas quedarán con tipo_id = NULL
        $tipo->delete();
        return redirect()->route('gestion-disciplinaria.tipos.index')->with('success', 'Tipo eliminado correctamente.');
    }
}
