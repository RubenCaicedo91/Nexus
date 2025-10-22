<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Institucion;

class InstitucionController extends Controller
{
    // Mostrar los datos de la institución
    public function index()
    {
        $institucion = Institucion::first(); // Solo hay una institución en el sistema
        return response()->json($institucion);
    }

    // Actualizar los datos de la institución
    public function update(Request $request, $id)
    {
        $institucion = Institucion::findOrFail($id);
        $institucion->update($request->all());

        return response()->json([
            'message' => 'Datos institucionales actualizados correctamente',
            'data' => $institucion
        ]);
    }

    // Crear la institución (solo si aún no existe)
    public function store(Request $request)
    {
        $institucion = Institucion::create($request->all());
        return response()->json([
            'message' => 'Institución creada correctamente',
            'data' => $institucion
        ]);
    }
}