<?php

namespace App\Http\Controllers;

use App\Models\Matricula;
use App\Models\User; // Assuming students are users
use App\Models\RolesModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MatriculaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Usar paginación por defecto para la lista (mejor experiencia y compatibilidad con ->links())
        $matriculas = Matricula::with('user')->orderBy('created_at', 'desc')->paginate(10);
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
    /**
     * Genera un identificador único legible para la carpeta del estudiante.
     * Ahora devuelve "{slug}_{id}" para evitar colisiones cuando dos estudiantes
     * tienen el mismo nombre (ej: "juan_perez_123").
     */
    private function studentSlugFromId(int $userId): string
    {
        $user = \App\Models\User::find($userId);
        $name = $user->name ?? 'desconocido';
        $slug = Str::slug(trim($name), '_'); // ejemplo: "Juan Pérez" -> "juan_perez"
        return $slug . '_' . $userId; // ejemplo: "juan_perez_123"
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

    /**
     * Construye la carpeta destino en el disco FTP.
     * Siempre devuelve: estudiante/{slug_unico}
     * Normaliza la entrada para evitar que se acumulen prefijos repetidos
     * Ejemplo: si accidentalmente se pasa "estudiante/juan" o "estudiante/estudiante/juan",
     * se extrae el último segmento y se genera "estudiante/juan".
     */
    private function buildTargetFolder(string $studentSlug, string $campo): string
    {
        // Extraer último segmento por si ya viene con prefijos
        $trimmed = trim($studentSlug, '/');
        $parts = preg_split('#/+#', $trimmed);
        $last = end($parts) ?: $trimmed;

        // Sanitizar el slug para que sea seguro como nombre de carpeta
        $safe = Str::slug($last, '_');

        // Devolver solo el identificador seguro del estudiante (sin prefijo)
        return $safe;
    }

    /**
     * Normaliza cualquier ruta objetivo para colapsar ramificaciones repetidas.
     * Extrae el último segmento que parezca un identificador de estudiante
     * (por ejemplo: 'estudiante' o 'estudiante_14') y devuelve siempre
     * 'estudiante/{segmento}'. Esto evita rutas como
     * 'estudiante/estudiante/estudiante_14/estudiante/estudiante_14...'
     */
    private function normalizeTargetFolder(string $folder): string
    {
        $trimmed = trim($folder, '/');

        // Buscar coincidencias del patrón 'estudiante' o 'estudiante_{id}'
            if (preg_match_all('/estudiante(?:_[0-9]+)?/i', $trimmed, $matches) && !empty($matches[0])) {
            $last = end($matches[0]);
            $safe = Str::slug($last, '_');
            return $safe;
        }

        // Si no hay coincidencias, tomar el último segmento cualquiera
        $parts = preg_split('#/+#', $trimmed);
        $last = end($parts) ?: $trimmed;
        $safe = Str::slug($last, '_');
        return $safe;
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
    // Carpeta base para el estudiante (sera `estudiante/{slug}`)
    $safeStudentSegment = $this->normalizeTargetFolder($slugEstudiante);
    $carpeta_base = 'estudiante/' . $safeStudentSegment;

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
            // Destino final: estudiante/{slug}
            $dir = $carpeta_base;

            if ($archivo) {
                // Crear carpeta si no existe
                if (! Storage::disk('ftp_matriculas')->exists($dir)) {
                    Storage::disk('ftp_matriculas')->makeDirectory($dir);
                }

                // Sanear el nombre original y evitar caracteres inválidos
                $origName = $archivo->getClientOriginalName();
                $ext = $archivo->getClientOriginalExtension();
                $base = pathinfo($origName, PATHINFO_FILENAME);
                $safeBase = Str::slug($base, '_') ?: 'file';
                $filename = $safeBase . '.' . ($ext ?: $archivo->extension());

                // Usar putFileAs para controlar el nombre remoto (evita caracteres/formatos inválidos)
                $stored = Storage::disk('ftp_matriculas')->putFileAs($dir, $archivo, $filename);
                // putFileAs retorna la ruta relativa en el disco o false si falla
                if ($stored === false) {
                    // Guardar error y continuar; marcar que faltan archivos para estado
                    $faltan = true;
                } else {
                    $rutas[$campo] = $stored;
                }
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
            // Usar la misma carpeta base: estudiante/{slug}
            $dir = 'estudiante/' . $this->normalizeTargetFolder($slugEstudiante);

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

                // Crear carpeta si no existe
                if (! Storage::disk('ftp_matriculas')->exists($dir)) {
                    Storage::disk('ftp_matriculas')->makeDirectory($dir);
                }

                $archivo = $request->file($campo);

                // Sanear y construir nombre seguro
                $origName = $archivo->getClientOriginalName();
                $ext = $archivo->getClientOriginalExtension();
                $base = pathinfo($origName, PATHINFO_FILENAME);
                $safeBase = Str::slug($base, '_') ?: 'file';
                $filename = $safeBase . '.' . ($ext ?: $archivo->extension());

                $stored = Storage::disk('ftp_matriculas')->putFileAs($dir, $archivo, $filename);
                if ($stored !== false) {
                    $matricula->$campo = $stored;
                }
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
        $userId = $matricula->user_id;
        // Preparar verificación de configuración FTP para evitar excepciones cuando no está definido
        $ftpConfig = config('filesystems.disks.ftp_matriculas') ?? null;
        $ftpHost = is_array($ftpConfig) && array_key_exists('host', $ftpConfig) ? $ftpConfig['host'] : null;
        $ftpConfigured = ! empty($ftpHost) && $ftpHost !== 'invalid://host-not-set';

        // Borrar archivos referenciados en el modelo si existen
        $campos = ['documento_identidad', 'rh', 'certificado_medico', 'certificado_notas'];
        foreach ($campos as $campo) {
            if ($matricula->$campo) {
                if ($ftpConfigured) {
                    try {
                        Storage::disk('ftp_matriculas')->delete($matricula->$campo);
                    } catch (\Exception $e) {
                        // Loguear pero continuar con el flujo de borrado de la matrícula
                        Log::error('Error al borrar archivo en FTP al eliminar matrícula', ['error' => $e->getMessage(), 'matricula_id' => $matricula->id, 'campo' => $campo]);
                    }
                } else {
                    Log::warning('FTP no configurado, no se intentó borrar archivo remoto', ['matricula_id' => $matricula->id, 'campo' => $campo]);
                }
            }
        }

        // Si no hay otras matrículas para este usuario, eliminar la carpeta del estudiante
        $existenOtras = Matricula::where('user_id', $userId)->where('id', '!=', $matricula->id)->exists();
        if (! $existenOtras) {
            $slug = $this->studentSlugFromId($userId);
            $folder = 'estudiante/' . $slug;
            if ($ftpConfigured) {
                try {
                    if (Storage::disk('ftp_matriculas')->exists($folder)) {
                        Storage::disk('ftp_matriculas')->deleteDirectory($folder);
                    }
                } catch (\Exception $e) {
                    Log::error('Error al borrar carpeta de estudiante en FTP al eliminar matrícula', ['error' => $e->getMessage(), 'matricula_id' => $matricula->id, 'folder' => $folder]);
                }
            } else {
                Log::warning('FTP no configurado, no se intentó borrar carpeta de estudiante', ['matricula_id' => $matricula->id, 'folder' => $folder]);
            }
        }

        $matricula->delete();

        return redirect()->route('matriculas.index')
                         ->with('success', 'Matrícula eliminada exitosamente.');
    }

    /**
     * Servir (visualizar/descargar inline) un archivo asociado a una matrícula.
     * Se espera que $campo sea uno de los campos: documento_identidad, rh, certificado_medico, certificado_notas
     */
    public function archivo(Matricula $matricula, $campo)
    {
        $allowed = ['documento_identidad', 'rh', 'certificado_medico', 'certificado_notas'];
        if (! in_array($campo, $allowed)) {
            abort(404);
        }

        $path = $matricula->$campo;
        if (! $path) {
            abort(404);
        }

        $disk = Storage::disk('ftp_matriculas');

        // Intentar abrir un stream desde el disco configurado
        if (! $disk->exists($path)) {
            // Fallback: buscar por nombre de archivo dentro del disco (caso de carpetas anidadas)
            $basename = basename($path);
            try {
                Log::info('Matricula archivo(): ruta no existe, intentando fallback por basename', ['matricula_id' => $matricula->id, 'campo' => $campo, 'ruta_bd' => $path]);
                $all = $disk->allFiles('estudiante');
            } catch (\Exception $e) {
                Log::error('Matricula archivo(): error listando archivos FTP en fallback', ['error' => $e->getMessage(), 'matricula_id' => $matricula->id, 'campo' => $campo]);
                abort(404);
            }

            $found = null;
            foreach ($all as $candidate) {
                if (strtolower(basename($candidate)) === strtolower($basename)) {
                    $found = $candidate;
                    break;
                }
            }

            if (! $found) {
                Log::warning('Matricula archivo(): fallback no encontró coincidencias', ['matricula_id' => $matricula->id, 'campo' => $campo, 'basename' => $basename]);
                abort(404);
            }

            Log::info('Matricula archivo(): fallback encontró archivo', ['matricula_id' => $matricula->id, 'campo' => $campo, 'ruta_encontrada' => $found]);
            $path = $found;
        }

        $stream = $disk->readStream($path);
        if (! $stream) {
            Log::error('Matricula archivo(): readStream devolvió false', ['matricula_id' => $matricula->id, 'campo' => $campo, 'path' => $path]);
            abort(500, 'No se pudo leer el archivo.');
        }

        // Determinar mime por la extensión como fallback (evita llamadas específicas del adapter)
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $map = [
            'pdf' => 'application/pdf',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
        ];
        $mime = $map[$ext] ?? 'application/octet-stream';

        $filename = basename($path);

        return response()->stream(function () use ($stream) {
            fpassthru($stream);
        }, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => "inline; filename=\"{$filename}\"",
        ]);
    }
}
