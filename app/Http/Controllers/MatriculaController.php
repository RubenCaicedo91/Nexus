<?php

namespace App\Http\Controllers;

use App\Models\Matricula;
use App\Models\User; // Assuming students are users
use App\Models\RolesModel;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
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

    // =====================
    // Helpers de subida FTP
    // =====================

    private function getStudentBaseDir(int $userId): string
    {
        $slugEstudiante = $this->studentSlugFromId($userId);
        $safeStudentSegment = $this->normalizeTargetFolder($slugEstudiante);
        return 'estudiante/' . $safeStudentSegment;
    }

    private function ensureDir(string $dir): void
    {
        if (! Storage::disk('ftp_matriculas')->exists($dir)) {
            Storage::disk('ftp_matriculas')->makeDirectory($dir);
        }
    }

    private function safeFilename(UploadedFile $file, string $prefix): string
    {
        $origName = $file->getClientOriginalName();
        $ext = $file->getClientOriginalExtension() ?: $file->extension();
        $base = pathinfo($origName, PATHINFO_FILENAME);
        $safeBase = Str::slug($base, '_') ?: 'file';
        return $prefix . '_' . $safeBase . '.' . $ext;
    }

    private function uploadToFtp(string $dir, UploadedFile $file, string $filename): ?string
    {
        $this->ensureDir($dir);
        $stored = Storage::disk('ftp_matriculas')->putFileAs($dir, $file, $filename);
        return $stored === false ? null : $stored;
    }

    private function uploadDocumentoIdentidad(int $userId, UploadedFile $file): ?string
    {
        $dir = $this->getStudentBaseDir($userId);
        $filename = $this->safeFilename($file, 'documento_identidad');
        return $this->uploadToFtp($dir, $file, $filename);
    }

    private function uploadRh(int $userId, UploadedFile $file): ?string
    {
        $dir = $this->getStudentBaseDir($userId);
        $filename = $this->safeFilename($file, 'rh');
        return $this->uploadToFtp($dir, $file, $filename);
    }

    private function uploadCertificadoMedico(int $userId, UploadedFile $file): ?string
    {
        $dir = $this->getStudentBaseDir($userId);
        $filename = $this->safeFilename($file, 'certificado_medico');
        return $this->uploadToFtp($dir, $file, $filename);
    }

    private function uploadCertificadoNotas(int $userId, UploadedFile $file): ?string
    {
        $dir = $this->getStudentBaseDir($userId);
        $filename = $this->safeFilename($file, 'certificado_notas');
        return $this->uploadToFtp($dir, $file, $filename);
    }
    /**
     * Sube un archivo según el nombre del campo del formulario.
     */
    private function uploadByFieldName(int $userId, string $campo, UploadedFile $file): ?string
    {
        switch ($campo) {
            case 'documento_identidad':
                return $this->uploadDocumentoIdentidad($userId, $file);
            case 'rh':
                return $this->uploadRh($userId, $file);
            case 'certificado_medico':
                return $this->uploadCertificadoMedico($userId, $file);
            case 'certificado_notas':
                return $this->uploadCertificadoNotas($userId, $file);
            default:
                return null;
        }
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

        $userId = (int) $request->user_id;
        $tipo_usuario = $request->input('tipo_usuario');

        $campos = ['documento_identidad', 'rh', 'certificado_medico'];
        if ($tipo_usuario === 'antiguo') {
            $campos[] = 'certificado_notas';
        }

        $rutas = [];
        $faltan = false;

        foreach ($campos as $campo) {
            if ($request->hasFile($campo)) {
                $path = $this->uploadByFieldName($userId, $campo, $request->file($campo));
                if ($path) {
                    $rutas[$campo] = $path;
                } else {
                    $faltan = true;
                }
            } else {
                $faltan = true;
            }
        }

        $estado = $faltan ? 'falta de documentacion' : $request->input('estado', 'activo');

        $matricula = new Matricula();
        $matricula->user_id = $userId;
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

        $documentos = [
            'documento_identidad',
            'rh',
            'certificado_medico',
            'certificado_notas'
        ];

        foreach ($documentos as $campo) {
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

                $uploaded = $this->uploadByFieldName((int)$request->user_id, $campo, $request->file($campo));
                if ($uploaded) {
                    $matricula->$campo = $uploaded;
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

        // Borrar archivos referenciados en el modelo si existen
        $campos = ['documento_identidad', 'rh', 'certificado_medico', 'certificado_notas'];
        foreach ($campos as $campo) {
            if ($matricula->$campo) {
                Storage::disk('ftp_matriculas')->delete($matricula->$campo);
            }
        }

        // Si no hay otras matrículas para este usuario, eliminar la carpeta del estudiante
        $existenOtras = Matricula::where('user_id', $userId)->where('id', '!=', $matricula->id)->exists();
        if (! $existenOtras) {
            $slug = $this->studentSlugFromId($userId);
            $folder = 'estudiante/' . $slug;
            if (Storage::disk('ftp_matriculas')->exists($folder)) {
                Storage::disk('ftp_matriculas')->deleteDirectory($folder);
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
        $allowed = ['documento_identidad', 'rh', 'certificado_medico', 'certificado_notas', 'comprobante_pago'];
        if (! in_array($campo, $allowed)) {
            Log::warning('Matricula archivo(): campo no permitido', ['campo' => $campo, 'matricula_id' => $matricula->id]);
            abort(404);
        }

        $path = $matricula->$campo;
        Log::info('Matricula archivo(): requested', ['matricula_id' => $matricula->id, 'campo' => $campo, 'ruta_bd' => $path]);
        if (! $path) {
            Log::warning('Matricula archivo(): ruta vacía en BD', ['campo' => $campo, 'matricula_id' => $matricula->id]);
            abort(404);
        }

        $disk = Storage::disk('ftp_matriculas');

        // Comprobar existencia directa y capturar excepciones del adapter
        try {
            $exists = $disk->exists($path);
        } catch (\Exception $e) {
            $exists = false;
            Log::error('Matricula archivo(): error calling exists()', ['error' => $e->getMessage(), 'matricula_id' => $matricula->id, 'path' => $path]);
        }

        // Si no existe exactamente, intentar varias estrategias de fallback
        if (! $exists) {
            // 1) Probar variaciones agregando prefijos repetidos 'estudiante/' (caso de rutas con duplicado)
            $triedCandidates = [];
            $normalized = ltrim($path, '/');
            for ($i = 1; $i <= 3; $i++) {
                $candidate = str_repeat('estudiante/', $i) . $normalized;
                $triedCandidates[] = $candidate;
                try {
                    if ($disk->exists($candidate)) {
                        Log::info('Matricula archivo(): found by prefix candidate', ['matricula_id' => $matricula->id, 'candidate' => $candidate]);
                        $path = $candidate;
                        $exists = true;
                        break;
                    }
                } catch (\Exception $e) {
                    Log::warning('Matricula archivo(): error checking candidate exists', ['candidate' => $candidate, 'error' => $e->getMessage()]);
                }
            }

            // Si no lo encontramos por prefijos, continuar con búsqueda por basename
            if (! $exists) {
            $basename = basename($path);
            try {
                $baseDir = $this->getStudentBaseDir((int)$matricula->user_id);
                Log::info('Matricula archivo(): ruta no existe, intentando fallback', [
                    'matricula_id' => $matricula->id,
                    'campo' => $campo,
                    'ruta_bd' => $path,
                    'base_dir' => $baseDir,
                ]);

                // Listar dentro de la carpeta del estudiante y en el root 'estudiante' para cubrir prefijos repetidos
                $all = [];
                try {
                    $all = $disk->allFiles($baseDir);
                } catch (\Exception $e) {
                    // ignore - intentaremos con el root abajo
                    Log::warning('Matricula archivo(): no se pudo listar baseDir, se intentará root', ['baseDir' => $baseDir, 'error' => $e->getMessage()]);
                }

                try {
                    $rootAll = $disk->allFiles('estudiante');
                    // merge manteniendo valores
                    $all = array_values(array_unique(array_merge($all, $rootAll)));
                } catch (\Exception $e) {
                    Log::warning('Matricula archivo(): no se pudo listar root estudiante', ['error' => $e->getMessage()]);
                }

                Log::info('Matricula archivo(): cantidad archivos escaneados en fallback', ['count' => count($all), 'matricula_id' => $matricula->id, 'tried_prefix_candidates' => $triedCandidates]);
            } catch (\Exception $e) {
                Log::error('Matricula archivo(): error en fallback listando FTP', [
                    'error' => $e->getMessage(),
                    'matricula_id' => $matricula->id,
                    'campo' => $campo,
                ]);
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
                Log::warning('Matricula archivo(): fallback sin coincidencias en baseDir/root', [
                    'matricula_id' => $matricula->id,
                    'campo' => $campo,
                    'basename' => $basename,
                    'escaneados' => count($all),
                ]);
                abort(404);
            }

            Log::info('Matricula archivo(): fallback encontró archivo', [
                'matricula_id' => $matricula->id,
                'campo' => $campo,
                'ruta_encontrada' => $found,
            ]);
            $path = $found;
        }

        try {
            $stream = $disk->readStream($path);
        } catch (\Exception $e) {
            Log::error('Matricula archivo(): excepción en readStream', [
                'error' => $e->getMessage(),
                'matricula_id' => $matricula->id,
                'campo' => $campo,
                'path' => $path,
            ]);
            abort(500, 'No se pudo leer el archivo.');
        }

        if (! $stream) {
            Log::error('Matricula archivo(): readStream devolvió false', [
                'matricula_id' => $matricula->id,
                'campo' => $campo,
                'path' => $path,
            ]);
            abort(500, 'No se pudo leer el archivo.');
        }

        // Determinar mime por extensión como fallback
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $map = [
            'pdf'  => 'application/pdf',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'gif'  => 'image/gif',
            'webp' => 'image/webp',
            'bmp'  => 'image/bmp',
        ];
        $mime = $map[$ext] ?? 'application/octet-stream';

        $filename = basename($path);

        // Intentar obtener tamaño (puede no estar soportado por algunos adaptadores FTP)
        $length = null;
        try {
            $length = $disk->size($path);
        } catch (\Throwable $t) {
            $length = null;
        }

        return response()->stream(function () use ($stream) {
            fpassthru($stream);
            if (is_resource($stream)) {
                @fclose($stream);
            }
        }, 200, array_filter([
            'Content-Type' => $mime,
            'Content-Disposition' => "inline; filename=\"{$filename}\"",
            'Content-Length' => $length,
            'Cache-Control' => 'private, max-age=0, no-cache',
        ], function($v) { return $v !== null; }));
    }

}






}






