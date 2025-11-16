<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Nota;
use App\Models\Actividad;

class ActividadesController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    // Listar actividades de una nota
    public function index(Nota $nota)
    {
        $nota->load(['matricula.user', 'materia', 'actividades']);
        return view('actividades.index', compact('nota'));
    }

    // Formulario crear actividad
    public function create(Nota $nota)
    {
        return view('actividades.create', compact('nota'));
    }

    // Guardar actividad
    public function store(Request $request, Nota $nota)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'valor' => 'required|numeric|min:0|max:5'
        ]);

        $actividad = Actividad::create([
            'nota_id' => $nota->id,
            'nombre' => $validated['nombre'],
            'valor' => $validated['valor']
        ]);

        // Recalcular la nota asociada después de crear la actividad
        $nota->refresh();
        $nota->recalculateFromActividades();

        return redirect()->route('notas.actividades.index', $nota)->with('success', 'Actividad guardada y nota actualizada');
    }

    // Eliminar actividad
    public function destroy(Nota $nota, Actividad $actividad)
    {
        if ($actividad->nota_id != $nota->id) {
            abort(404);
        }
        $actividad->delete();

        // Recalcular la nota asociada después de eliminar la actividad
        $nota->refresh();
        $nota->recalculateFromActividades();

        return redirect()->route('notas.actividades.index', $nota)->with('success', 'Actividad eliminada y nota actualizada');
    }
}
