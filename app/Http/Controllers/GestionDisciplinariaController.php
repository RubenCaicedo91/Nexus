<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GestionDisciplinariaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
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
                $path = $file->store('disciplinaria/' . now()->format('Ymd'), 'local'); // o 'ftp_matriculas'
                $evidencias[] = $path;
            }
        }

        $reporte = ReporteDisciplinario::create(array_merge($validated, [
            'reporter_id' => auth()->id(),
            'evidencia' => $evidencias,
        ]));

        return redirect()->route('disciplinaria.show', $reporte)->with('success','Reporte creado');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function asignarSancion(Request $request, $id)
    {
        $reporte = ReporteDisciplinario::findOrFail($id);
        $validated = $request->validate([
            'sancion_id' => 'required|exists:sanciones,id',
            'comentario' => 'nullable|string',
        ]);

        $reporte->sancion_id = $validated['sancion_id'];
        $reporte->estado = 'resuelto'; // o criterio que quieras
        $reporte->save();

        // opcional: guardar historial de acciones

        return back()->with('success','Sanción asignada');
    }
}

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
