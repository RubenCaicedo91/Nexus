<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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
        return view('gestion.horarios');
    }
}
