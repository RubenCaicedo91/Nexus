<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Horario;
use App\Models\Curso;

class GestionAcademicaController extends Controller
{
    public function index()
    {
        return view('gestion.index');
    }

    // 📘 CURSOS

    public function crearCurso()
    {
        return view('gestion.crear_curso');
    }

    public function guardarCurso(Request $request)
    {
        $request->validate([
            'nivel' => 'required|string',
            'grupo' => 'required|string',
            'descripcion' => 'nullable|string',
        ]);

        Curso::create([
            'nombre' => $request->nivel . ' ' . $request->grupo,
            'descripcion' => $request->descripcion,
        ]);

        return redirect()->route('cursos.panel')->with('success', 'Curso creado correctamente.');
    }

    public function listarCursos()
    {
        $cursos = Curso::all();
        return view('gestion.index', compact('cursos'));
    }

    public function editarCurso($id)
    {
        $curso = Curso::findOrFail($id);
        return view('gestion.editar_curso', compact('curso'));
    }

    public function actualizarCurso(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required|string',
            'descripcion' => 'nullable|string',
        ]);

        $curso = Curso::findOrFail($id);
        $curso->update($request->only(['nombre', 'descripcion']));

        // Redirigimos al panel de cursos para mantener consistencia con crear/guardar/eliminar
        return redirect()->route('cursos.panel')->with('success', 'Curso actualizado correctamente.');
    }

    public function eliminarCurso($id)
    {
        $curso = Curso::findOrFail($id);
        $curso->delete();

        return redirect()->route('cursos.panel')->with('success', 'Curso eliminado correctamente.');
    }

    public function panelCursos()
    {
        try {
            $cursos = Curso::all();
            $errorMessage = null;
        } catch (\Throwable $e) {
            // Registro en log para depuración y devolvemos una lista vacía con mensaje de error
            logger()->error('Error al cargar cursos: ' . $e->getMessage());
            $cursos = collect();
            $errorMessage = 'Error: la tabla de cursos no existe o la conexión a la base de datos falló. Ejecuta las migraciones (php artisan migrate) o revisa la configuración de la BD.';
        }

        return view('gestion.panel_cursos', compact('cursos', 'errorMessage'));
    }

    // 🕒 HORARIOS

    public function horarios()
    {
        $horarios = Horario::all();
        return view('gestion.horarios', compact('horarios'));
    }

    public function editarHorario($id)
    {
        $horario = Horario::findOrFail($id);
        return view('gestion.editar_horario', compact('horario'));
    }

    public function actualizarHorario(Request $request, $id)
    {
        $request->validate([
            'curso' => 'required|string',
            'dia' => 'required|string',
            'hora' => 'required',
        ]);

        $horario = Horario::findOrFail($id);
        $horario->update($request->only(['curso', 'dia', 'hora']));

        return redirect()->route('gestion.horarios')->with('success', 'Horario actualizado correctamente.');
    }

    public function eliminarHorario($id)
    {
        $horario = Horario::findOrFail($id);
        $horario->delete();

        return redirect()->route('gestion.horarios')->with('success', 'Horario eliminado correctamente.');
    }


}
