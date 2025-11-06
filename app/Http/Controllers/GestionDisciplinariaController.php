<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sancion;
use App\Models\ReporteDisciplinario;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ViewErrorBag;

class GestionDisciplinariaController extends Controller
{
    // ---------------- Resource methods (basic stubs / implementations) ----------------
    /**
     * Display a listing of sanciones.
     */
    public function index()
    {
        $sanciones = Sancion::all();
        return view('gestion-disciplinaria.index', compact('sanciones'));
    }

    /**
     * Show the form for creating a new report (resource create).
     */
    public function create()
    {
        return $this->mostrarFormularioSancion();
    }

    /**
     * Store a newly created ReporteDisciplinario in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'fecha_incidencia' => 'required|date',
            'descripcion' => 'required|string|min:10',
            'gravedad' => 'required|in:baja,media,alta',
            'evidencia.*' => 'nullable|file|mimes:pdf,jpg,png|max:20480',
        ]);

        // subir evidencia al disco local o ftp
        $evidencias = [];
        if ($request->hasFile('evidencia')) {
            foreach ($request->file('evidencia') as $file) {
                $path = $file->store('disciplinaria/' . now()->format('Ymd'), 'local');
                $evidencias[] = $path;
            }
        }

        $reporte = ReporteDisciplinario::create(array_merge($validated, [
            'reporter_id' => Auth::id(),
            'evidencia' => $evidencias,
        ]));

        return redirect()->route('disciplinaria.show', $reporte)->with('success','Reporte creado');
    }

    public function show(string $id)
    {
        $reporte = ReporteDisciplinario::find($id);
        if ($reporte) {
            return view('gestion-disciplinaria.show', compact('reporte'));
        }
        abort(404);
    }

    public function edit(string $id)
    {
        // stub
    }

    public function update(Request $request, string $id)
    {
        // stub
    }

    public function destroy(string $id)
    {
        // stub
    }

    // ---------------- Additional actions ----------------
    public function asignarSancion(Request $request, $id)
    {
        $reporte = ReporteDisciplinario::findOrFail($id);
        $validated = $request->validate([
            'sancion_id' => 'required|exists:sanciones,id',
            'comentario' => 'nullable|string',
        ]);

        $reporte->sancion_id = $validated['sancion_id'];
        $reporte->estado = 'resuelto';
        $reporte->save();

        return back()->with('success','Sanción asignada');
    }

    // Formulario y acciones relacionadas a Sancion
    public function mostrarFormularioSancion()
    {
        // Asegurar que la vista tenga un objeto $errors incluso en pruebas CLI
        $errors = session()->get('errors', new ViewErrorBag());
        return view('gestion-disciplinaria.registrar_sancion')->with('errors', $errors);
    }

    public function registrarSancion(Request $request)
    {
        // Validación mínima
        $request->validate([
            'usuario_id' => 'required|exists:users,id',
            'descripcion' => 'required|string',
            'tipo' => 'required|string',
            'fecha' => 'required|date',
        ]);

        Sancion::create([
            'usuario_id' => $request->usuario_id,
            'descripcion' => $request->descripcion,
            'tipo' => $request->tipo,
            'fecha' => $request->fecha,
        ]);

        return redirect()->route('gestion-disciplinaria.index');
    }

    public function historialSanciones($id)
    {
        $sanciones = Sancion::where('usuario_id', $id)->get();
        return view('gestion-disciplinaria.historial_sanciones', compact('sanciones'));
    }

    public function generarReporte()
    {
        $reporte = Sancion::all();
        return view('gestion-disciplinaria.reporte', compact('reporte'));
    }
}
