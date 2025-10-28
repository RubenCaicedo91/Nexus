<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Horario;

class GestionAcademicaController extends Controller
{
    public function index()
    {
        return view('gestion.index');
    }

    public function crearCurso()
    {
        return view('gestion.crear_curso');
    }

    public function editarCurso()
    {
        return view('gestion.editar_curso');
    }

    public function horarios()
    {
        $horarios = Horario::all();
        return view('gestion.horarios', compact('horarios'));
    }
}
