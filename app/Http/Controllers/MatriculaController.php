<?php

namespace App\Http\Controllers;

use App\Models\Matricula;
use App\Models\User; // Assuming students are users
use App\Models\RolesModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MatriculaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $matriculas = Matricula::with('user')->get();
        return view('matriculas.index', compact('matriculas'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Obtener dinámicamente el id del rol 'Estudiante' en lugar de usar un id fijo
        $studentRole = RolesModel::where('nombre', 'Estudiante')->first();
        if ($studentRole) {
            $students = User::where('roles_id', $studentRole->id)->get();
        } else {
            // Si no existe el rol, devolver colección vacía para evitar errores en la vista
            $students = collect();
        }

        return view('matriculas.create', compact('students'));
    }

    // Helpers nuevos para rutas ESTABLES
    private function studentSlugFromId(int $userId): string
    {
        $name = optional(\App\Models\User::find($userId))->name ?? 'desconocido';
        return Str::slug(trim($name), '_'); // ejemplo: "Juan Pérez" -> "juan_perez"
    }

    private function subfolderMap(): array
    {
        return [
            'documento_identidad' => 'documento',
            'rh'                 => 'rh',
            'certificado_medico' => 'certificado_medico',
            'certificado_notas'  => 'registro_de_notas',
        ];
    }

    private function buildTargetFolder(string $studentSlug, string $campo): string
    {
        $map = $this->subfolderMap();
        $sub = $map[$campo] ?? $campo;
        $base = trim($studentSlug, '/');        // "juan_perez"
        $path = $base . '/' . trim($sub, '/');  // "juan_perez/documento"

        // Normalizar por si el slug se repite accidentalmente
        $pattern = '#(?:^|/)(' . preg_quote($base, '#') . ')(?:/\1)+(?=/|$)#i';
        $path = preg_replace($pattern, '$1', $path);

        return $path;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'fecha_matricula' => 'required|date',
            'estado' => 'required|string|max:255',
            'documento_identidad' => 'nullable|file|max:20480',
            'rh' => 'nullable|file|max:20480',
            'certificado_medico' => 'nullable|file|max:20480',
            'certificado_notas' => 'nullable|file|max:20480',
        ]);

        $slugEstudiante = $this->studentSlugFromId((int)$request->user_id);
        // Carpeta base para el estudiante (no modificar dentro del bucle)
        $carpeta_base = $slugEstudiante;

        $documentos = [
            'documento_identidad' => $request->file('documento_identidad'),
            'rh'                  => $request->file('rh'),
            'certificado_medico'  => $request->file('certificado_medico'),
        ];

        $tipo_usuario = $request->input('tipo_usuario');
        if ($tipo_usuario === 'antiguo') {
            $documentos['certificado_notas'] = $request->file('certificado_notas');
        }

        $faltan = false;
        $rutas = [];

        foreach ($documentos as $campo => $archivo) {
            $carpeta_documento = $this->buildTargetFolder($slugEstudiante, $campo); // "juan_perez/documento"
            if ($archivo) {
                // Crear subcarpeta solo si no existe
                if (!Storage::disk('ftp_matriculas')->exists($carpeta_documento)) {
                    Storage::disk('ftp_matriculas')->makeDirectory($carpeta_documento);
                }
                $ruta = $archivo->store($carpeta_documento, 'ftp_matriculas');
                $rutas[$campo] = $ruta;
            } else {
                $faltan = true;
            }
        }

        $estado = $faltan ? 'falta de documentacion' : $request->input('estado', 'activo');

        // Guardar la matrícula (ajusta según tu modelo)
        $matricula = new Matricula();
        $matricula->user_id = $request->user_id;
        $matricula->fecha_matricula = $request->fecha_matricula;
        $matricula->estado = $estado;
        $matricula->tipo_usuario = $tipo_usuario ?? null;
        $matricula->documento_identidad = $rutas['documento_identidad'] ?? null;
        $matricula->rh = $rutas['rh'] ?? null;
        $matricula->certificado_medico = $rutas['certificado_medico'] ?? null;
        $matricula->certificado_notas = $rutas['certificado_notas'] ?? null;
        $matricula->save();

        return redirect()->route('matriculas.index')->with('success', 'Matrícula creada correctamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Matricula $matricula)
    {
        return view('matriculas.show', compact('matricula'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Matricula $matricula)
    {
        // Obtener dinámicamente el id del rol 'Estudiante'
        $studentRole = RolesModel::where('nombre', 'Estudiante')->first();
        if ($studentRole) {
            $students = User::where('roles_id', $studentRole->id)->get();
        } else {
            $students = collect();
        }

        return view('matriculas.edit', compact('matricula', 'students'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $matricula = Matricula::findOrFail($id);

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'fecha_matricula' => 'required|date',
            'estado' => 'required|string|max:255',
            'documento_identidad' => 'nullable|file|max:20480',
            'rh' => 'nullable|file|max:20480',
            'certificado_medico' => 'nullable|file|max:20480',
            'certificado_notas' => 'nullable|file|max:20480',
        ]);

        $slugEstudiante = $this->studentSlugFromId((int)$request->user_id);

        $documentos = [
            'documento_identidad',
            'rh',
            'certificado_medico',
            'certificado_notas'
        ];

        foreach ($documentos as $campo) {
            $carpeta_documento = $this->buildTargetFolder($slugEstudiante, $campo); // "juan_perez/registro_de_notas"

            if ($request->has("delete_$campo")) {
                if ($matricula->$campo) {
                    Storage::disk('ftp_matriculas')->delete($matricula->$campo);
                    $matricula->$campo = null;
                }
            }

            if ($request->hasFile($campo)) {
                if ($matricula->$campo) {
                    Storage::disk('ftp_matriculas')->delete($matricula->$campo);
                }
                // Crear subcarpeta solo si no existe
                if (!Storage::disk('ftp_matriculas')->exists($carpeta_documento)) {
                    Storage::disk('ftp_matriculas')->makeDirectory($carpeta_documento);
                }
                $archivo = $request->file($campo);
                $ruta = $archivo->store($carpeta_documento, 'ftp_matriculas');
                $matricula->$campo = $ruta;
            }
        }

        $matricula->user_id = $request->user_id;
        $matricula->fecha_matricula = $request->fecha_matricula;
        $matricula->tipo_usuario = $request->tipo_usuario;

        $faltan = false;
        if (!$matricula->documento_identidad || !$matricula->rh || !$matricula->certificado_medico) {
            $faltan = true;
        }
        if ($request->tipo_usuario === 'antiguo' && !$matricula->certificado_notas) {
            $faltan = true;
        }

        $matricula->estado = $faltan ? 'falta de documentacion' : $request->estado;

        $matricula->save();

        return redirect()->route('matriculas.index')->with('success', 'Matrícula actualizada correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Matricula $matricula)
    {
        $matricula->delete();

        return redirect()->route('matriculas.index')
                         ->with('success', 'Matrícula eliminada exitosamente.');
    }
}
