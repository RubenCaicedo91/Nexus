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
use Illuminate\Support\Facades\Schema;
use App\Models\MatriculaComprobante;
use App\Models\Notificacion;


class MatriculaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');

        $this->middleware(function ($request, $next) {
            $user = Auth::user(); // ✅ Reconocido por Intelephense
            if (! $user) {
                abort(403, 'Acceso no autorizado');
            }

            $roleName = optional($user->role)->nombre ?? '';

            // Bloquear explícitamente al rol 'coordinador disciplina'
            if ($roleName && stripos($roleName, 'coordinador disciplina') !== false) {
                abort(403, 'Acceso no autorizado');
            }

            // Permitir usuarios con permiso gestionar_academica o roles administrativos
            if ((method_exists($user, 'hasPermission') && $user->hasPermission('gestionar_academica')) ||
                ($roleName && (
                    stripos($roleName, 'admin') !== false ||
                    stripos($roleName, 'administrador') !== false ||
                    stripos($roleName, 'rector') !== false
                )) ||
                (isset($user->roles_id) && (int)$user->roles_id === 1)
            ) {
                return $next($request);
            }

            abort(403, 'Acceso no autorizado');
        });
    }
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
    public function create(Request $request)
    {
        // Obtener dinámicamente el id del rol 'Estudiante' en lugar de usar un id fijo
        $studentRole = RolesModel::where('nombre', 'Estudiante')->first();
        if ($studentRole) {
            // Si la petición incluye 'q' (búsqueda), responder JSON filtrando
            // únicamente por número de documento (document_number).
            $q = trim($request->get('q', ''));
            if ($q !== '' || $request->ajax() || $request->wantsJson()) {
                if ($q === '') {
                    return response()->json(['data' => []]);
                }


                $students = User::where('roles_id', $studentRole->id)
                    ->where('document_number', 'like', "%{$q}%")
                    ->select('id','name','first_name','second_name','first_last','second_last','document_number','document_type','email','celular')
                    ->orderBy('document_number')
                    ->limit(50)
                    ->get();

                return response()->json(['data' => $students]);
            }

            $students = User::where('roles_id', $studentRole->id)->get();
        } else {
            // Si no existe el rol, devolver colección vacía para evitar errores en la vista
            $students = collect();
        }

        // Obtener lista de cursos y normalizar para mostrar solo el nombre base
        // Pero incluir únicamente aquellas bases que tienen al menos un curso
        // con sufijo de letra (ej: "Primero A", "Primero B"). De esta forma
        // no se mostrarán niveles que aún no tienen grupos asociados.
        $rawCursos = \App\Models\Curso::orderBy('nombre')->pluck('nombre');
        $baseCursos = [];
        foreach ($rawCursos as $nombre) {
            // Detectar nombres que terminan en una letra (posible sufijo de grupo)
            // Requerimos al menos un separador (espacio, guion, paréntesis o corchete)
            // antes de la letra para evitar coincidir con palabras que terminan
            // naturalmente en letra (ej: "Pre-jardín"). Ejemplo válido: "Primero A".
            if (preg_match('/^(.*?)[\s\-\(\[]+([A-Za-zÁÉÍÓÚÑáéíóúñ])$/u', trim($nombre), $m)) {
                $base = trim($m[1]);
                if ($base === '') {
                    $base = $nombre; // fallback
                }
                if (!in_array($base, $baseCursos, true)) {
                    $baseCursos[] = $base;
                }
            }
        }

        return view('matriculas.create', compact('students', 'baseCursos'));
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

    
    private function safeFilename(UploadedFile $file, string $prefix): string
    {
        // Devuelve un nombre seguro pero no realiza ninguna operación de subida.
        $origName = basename($file->getClientOriginalName());
        $ext = $file->getClientOriginalExtension() ?: $file->extension();
        $base = pathinfo($origName, PATHINFO_FILENAME);
        $safeBase = Str::slug($base, '_') ?: 'file';
        return $prefix . '_' . $safeBase . '.' . $ext;
    }

    private function uploadToFtp(string $dir, UploadedFile $file, string $filename): ?string
    {
        $dir = trim(str_replace('\\', '/', $dir), '/');
        $this->ensureDir($dir);

        // Sanitizar filename
        $filename = basename($filename);

        $disk = Storage::disk('ftp_matriculas');
        $candidate = $dir === '' ? $filename : ($dir . '/' . $filename);

        \Log::info('uploadToFtp: intentando subir archivo', ['dir' => $dir, 'candidate' => $candidate, 'filename' => $filename]);

        // Si el archivo ya existe, eliminarlo para reemplazar
        try {
            if ($disk->exists($candidate)) {
                \Log::info('uploadToFtp: archivo existe, intentando borrar', ['candidate' => $candidate]);
                $disk->delete($candidate);
            }
        } catch (\Exception $e) {
            \Log::warning('uploadToFtp: error al intentar borrar archivo existente', ['error' => $e->getMessage(), 'candidate' => $candidate]);
        }

        try {
            $stored = $disk->putFileAs($dir, $file, $filename);
        } catch (\Exception $e) {
            \Log::error('uploadToFtp: excepción al subir', ['error' => $e->getMessage(), 'dir' => $dir, 'filename' => $filename]);
            return null;
        }

        \Log::info('uploadToFtp: resultado de putFileAs', ['stored' => $stored]);
        return $stored === false ? null : $stored;
    }

    /**
     * Igual a uploadToFtp pero NO crea directorios. Intenta subir y reemplazar
     * el archivo si ya existe. Si la ruta de destino no existe, fallará y
     * devolverá null sin crear carpetas.
     */
    private function uploadToFtpNoCreate(string $dir, UploadedFile $file, string $filename): ?string
    {
        $dir = trim(str_replace('\\', '/', $dir), '/');

        // Sanitizar filename
        $filename = basename($filename);

        $disk = Storage::disk('ftp_matriculas');
        // Comprobar si la carpeta de destino existe en el disco.
        $dirExists = true;
        if ($dir !== '') {
            try {
                // Intentar listar archivos de la carpeta; si lanza excepción, asumimos que no existe
                $disk->allFiles($dir);
                $dirExists = true;
            } catch (\Exception $e) {
                $dirExists = false;
            }
        }

        // Si la carpeta no existe, haremos fallback a la raíz (no crear carpetas).
        if ($dirExists) {
            $candidate = $dir . '/' . $filename;
        } else {
            $candidate = $filename; // almacenar en root
            \Log::info('uploadToFtpNoCreate: carpeta destino no existe, guardando en root (no se crearán carpetas)', ['dir' => $dir, 'candidate' => $candidate]);
        }

        \Log::info('uploadToFtpNoCreate: intentando subir archivo (sin crear dirs)', ['dir' => $dir, 'candidate' => $candidate, 'filename' => $filename, 'dirExists' => $dirExists]);

        try {
            if ($disk->exists($candidate)) {
                \Log::info('uploadToFtpNoCreate: archivo existe, intentando borrar', ['candidate' => $candidate]);
                $disk->delete($candidate);
            }
        } catch (\Exception $e) {
            \Log::warning('uploadToFtpNoCreate: error al intentar borrar archivo existente', ['error' => $e->getMessage(), 'candidate' => $candidate]);
        }

        try {
            if ($dirExists) {
                $stored = $disk->putFileAs($dir, $file, $filename);
            } else {
                // Guardar en la raíz usando putFileAs con path vacío
                $stored = $disk->putFileAs('', $file, $filename);
            }
        } catch (\Exception $e) {
            \Log::error('uploadToFtpNoCreate: excepción al subir', ['error' => $e->getMessage(), 'dir' => $dir, 'filename' => $filename, 'dirExists' => $dirExists]);
            return null;
        }

        \Log::info('uploadToFtpNoCreate: resultado de putFileAs', ['stored' => $stored]);
        return $stored === false ? null : $stored;
    }

    /**
     * Fuerza la subida del archivo en la raíz del disco FTP sin crear carpetas.
     * Elimina el archivo existente si ya está presente y escribe el nuevo.
     */
    private function uploadToFtpRoot(UploadedFile $file, string $filename): ?string
    {
        $filename = basename($filename);
        $disk = Storage::disk('ftp_matriculas');

        $candidate = $filename;
        \Log::info('uploadToFtpRoot: intentando subir archivo al root', ['candidate' => $candidate, 'filename' => $filename]);

        // Antes de subir, eliminar cualquier archivo con el mismo basename en el disco
        try {
            $basename = strtolower($filename);
            $candidates = [];
            try {
                $allRoot = $disk->allFiles('');
                $candidates = array_merge($candidates, $allRoot);
            } catch (\Exception $e) {
                \Log::warning('uploadToFtpRoot: no se pudo listar root al buscar duplicados', ['error' => $e->getMessage()]);
            }

            try {
                $allEst = $disk->allFiles('estudiante');
                $candidates = array_merge($candidates, $allEst);
            } catch (\Exception $e) {
                // puede que no exista la carpeta 'estudiante' o no sea accesible
            }

            $candidates = array_values(array_unique($candidates));
            foreach ($candidates as $c) {
                if (strtolower(basename($c)) === $basename) {
                    try {
                        \Log::info('uploadToFtpRoot: borrando duplicado previo encontrado', ['path' => $c]);
                        $disk->delete($c);
                    } catch (\Exception $e) {
                        \Log::warning('uploadToFtpRoot: no se pudo borrar duplicado', ['path' => $c, 'error' => $e->getMessage()]);
                    }
                }
            }
        } catch (\Exception $e) {
            \Log::warning('uploadToFtpRoot: error buscando/borrando duplicados', ['error' => $e->getMessage()]);
        }

        try {
            // putFileAs con directorio vacío escribe en root
            $stored = $disk->putFileAs('', $file, $filename);
        } catch (\Exception $e) {
            \Log::error('uploadToFtpRoot: excepción al subir', ['error' => $e->getMessage(), 'filename' => $filename]);
            return null;
        }

        \Log::info('uploadToFtpRoot: resultado de putFileAs', ['stored' => $stored]);
        return $stored === false ? null : $stored;
    }

    /**
     * Normaliza una ruta para el disco FTP: convierte separadores,
     * elimina duplicados consecutivos y colapsa repeticiones completas.
     */
    private function normalizeDirPath(string $dir): string
    {
        // Normalizar separadores
        $dir = str_replace('\\', '/', $dir);
        $dir = trim($dir, '/');

        if ($dir === '') return '';

        $parts = preg_split('#/+#', $dir);

        // Eliminar duplicados consecutivos
        $clean = [];
        foreach ($parts as $p) {
            if ($p === '') continue;
            if (empty($clean) || end($clean) !== $p) {
                $clean[] = $p;
            }
        }

        $n = count($clean);
        // Si la ruta está compuesta por una repetición del mismo bloque, colapsarla
        for ($len = 1; $len <= intdiv($n, 2); $len++) {
            if ($n % $len !== 0) continue;
            $chunks = array_chunk($clean, $len);
            $allSame = true;
            foreach ($chunks as $chunk) {
                if ($chunk !== $chunks[0]) { $allSame = false; break; }
            }
            if ($allSame) {
                $clean = $chunks[0];
                break;
            }
        }

        return implode('/', $clean);
    }

    private function uploadDocumentoIdentidad(int $userId, UploadedFile $file): ?string
    {
        // Construir carpeta principal basada en el número de documento del usuario
        $user = \App\Models\User::find($userId);
        if (! $user) {
            \Log::warning('uploadDocumentoIdentidad: usuario no encontrado', ['userId' => $userId]);
            $docNumber = null;
        } else {
            $docNumber = $user->document_number ?? null;
            \Log::info('uploadDocumentoIdentidad: usuario encontrado', ['userId' => $userId, 'user_document_number' => $docNumber]);
        }

        // Normalizar el número de documento para usar como nombre de carpeta
        if (empty($docNumber)) {
            // fallback: usar id del usuario si no hay número de documento
            $docSegment = 'id_' . $userId;
            \Log::warning('uploadDocumentoIdentidad: document_number vacío, usando fallback id', ['userId' => $userId, 'docSegment' => $docSegment]);
        } else {
            $docSegment = preg_replace('/[^A-Za-z0-9_\-]/', '_', trim((string)$docNumber));
        }

        // Nombre de archivo: ID_<numero de documento>.<ext>
        $ext = $file->getClientOriginalExtension() ?: $file->extension();
        $safeDoc = $docSegment ?: 'sin_documento';
        $filename = 'ID_' . $safeDoc . '.' . $ext;

        // Guardar siempre en la raíz del disco para evitar cualquier creación de carpetas
        $stored = $this->uploadToFtpRoot($file, $filename);
        return $stored;
    }

    /**
     * Subida genérica para otros documentos: coloca el archivo dentro de la carpeta
     * base del estudiante y, si corresponde, en una subcarpeta por tipo.
     */
    private function uploadOtherDocument(int $userId, string $campo, UploadedFile $file): ?string
    {
        // Subida deshabilitada temporalmente.
        return null;
    }

    private function uploadRh(int $userId, UploadedFile $file): ?string
    {
        // Construir carpeta principal basada en el número de documento del usuario
        $user = \App\Models\User::find($userId);
        if (! $user) {
            \Log::warning('uploadRh: usuario no encontrado', ['userId' => $userId]);
            $docNumber = null;
        } else {
            $docNumber = $user->document_number ?? null;
            \Log::info('uploadRh: usuario encontrado', ['userId' => $userId, 'user_document_number' => $docNumber]);
        }

        if (empty($docNumber)) {
            // fallback: usar id del usuario si no hay número de documento
            $docSegment = 'id_' . $userId;
            \Log::warning('uploadRh: document_number vacío, usando fallback id', ['userId' => $userId, 'docSegment' => $docSegment]);
        } else {
            $docSegment = preg_replace('/[^A-Za-z0-9_\-]/', '_', trim((string)$docNumber));
        }

        // Nombre de archivo: RH_<numero de documento>.<ext>
        $ext = $file->getClientOriginalExtension() ?: $file->extension();
        $safeDoc = $docSegment ?: 'sin_documento';
        $filename = 'RH_' . $safeDoc . '.' . $ext;

        // Guardar siempre en la raíz del disco para evitar cualquier creación de carpetas
        $stored = $this->uploadToFtpRoot($file, $filename);
        return $stored;
    }

    private function uploadCertificadoMedico(int $userId, UploadedFile $file): ?string
    {
        $user = \App\Models\User::find($userId);
        $docNumber = $user->document_number ?? null;
        if (empty($docNumber)) {
            $docSegment = 'id_' . $userId;
            \Log::warning('uploadCertificadoMedico: document_number vacío, usando fallback id', ['userId' => $userId, 'docSegment' => $docSegment]);
        } else {
            $docSegment = preg_replace('/[^A-Za-z0-9_\-]/', '_', trim((string)$docNumber));
        }

        $ext = $file->getClientOriginalExtension() ?: $file->extension();
        $safeDoc = $docSegment ?: 'sin_documento';
        $filename = 'CM_' . $safeDoc . '.' . $ext;

        // Guardar siempre en la raíz del disco
        return $this->uploadToFtpRoot($file, $filename);
    }

    private function uploadCertificadoNotas(int $userId, UploadedFile $file): ?string
    {
        $user = \App\Models\User::find($userId);
        $docNumber = $user->document_number ?? null;
        if (empty($docNumber)) {
            $docSegment = 'id_' . $userId;
            \Log::warning('uploadCertificadoNotas: document_number vacío, usando fallback id', ['userId' => $userId, 'docSegment' => $docSegment]);
        } else {
            $docSegment = preg_replace('/[^A-Za-z0-9_\-]/', '_', trim((string)$docNumber));
        }

        $ext = $file->getClientOriginalExtension() ?: $file->extension();
        $safeDoc = $docSegment ?: 'sin_documento';
        $filename = 'CN_' . $safeDoc . '.' . $ext;

        // Guardar siempre en la raíz del disco
        return $this->uploadToFtpRoot($file, $filename);
    }

    private function uploadPagoMatricula(int $userId, UploadedFile $file): ?string
    {
        $user = \App\Models\User::find($userId);
        $docNumber = $user->document_number ?? null;
        if (empty($docNumber)) {
            $docSegment = 'id_' . $userId;
            \Log::warning('uploadPagoMatricula: document_number vacío, usando fallback id', ['userId' => $userId, 'docSegment' => $docSegment]);
        } else {
            $docSegment = preg_replace('/[^A-Za-z0-9_\-]/', '_', trim((string)$docNumber));
        }

        $ext = $file->getClientOriginalExtension() ?: $file->extension();
        $safeDoc = $docSegment ?: 'sin_documento';
        $base = 'PM_' . $safeDoc;

        $disk = Storage::disk('ftp_matriculas');

        // Buscar si ya existe el archivo sin sufijo
        $basenameNoSuffix = strtolower($base . '.' . $ext);
        $existsNoSuffix = false;
        $maxSuffix = 0;

        try {
            $all = [];
            try { $all = $disk->allFiles(''); } catch (\Exception $e) { $all = []; }
            try { $all = array_merge($all, $disk->allFiles('estudiante')); } catch (\Exception $e) { /* ignore */ }

            foreach ($all as $candidate) {
                $b = strtolower(basename($candidate));
                if ($b === $basenameNoSuffix) {
                    $existsNoSuffix = true;
                }
                // match suffix pattern PM_doc_01.ext
                if (preg_match('/^' . preg_quote(strtolower($base), '/') . '_(\d+)\.' . preg_quote(strtolower($ext), '/') . '$/', $b, $m)) {
                    $n = intval($m[1]);
                    if ($n > $maxSuffix) $maxSuffix = $n;
                }
            }
        } catch (\Exception $e) {
            \Log::warning('uploadPagoMatricula: no se pudieron listar archivos para comprobar sufijos', ['error' => $e->getMessage()]);
        }

        if (! $existsNoSuffix && $maxSuffix === 0) {
            $filename = $base . '.' . $ext;
        } else {
            // determinar siguiente sufijo
            $next = $maxSuffix + 1;
            $filename = $base . '_' . sprintf('%02d', $next) . '.' . $ext;
        }

        // Guardar en root usando putFileAs (no eliminar históricos)
        $filename = basename($filename);
        try {
            $stored = $disk->putFileAs('', $file, $filename);
            return $stored === false ? null : $stored;
        } catch (\Exception $e) {
            \Log::error('uploadPagoMatricula: error al subir comprobante', ['error' => $e->getMessage(), 'filename' => $filename]);
            return null;
        }
    }
    /**
     * Sube un archivo según el nombre del campo del formulario.
     */
    private function uploadByFieldName(int $userId, string $campo, UploadedFile $file): ?string
    {
        if ($campo === 'documento_identidad') {
            return $this->uploadDocumentoIdentidad($userId, $file);
        }

        if ($campo === 'rh') {
            return $this->uploadRh($userId, $file);
        }

        if ($campo === 'certificado_medico') {
            return $this->uploadCertificadoMedico($userId, $file);
        }

        if ($campo === 'certificado_notas') {
            return $this->uploadCertificadoNotas($userId, $file);
        }

        // Nuevo: manejo de comprobante de pago (PM_<documento>)
        if ($campo === 'comprobante_pago' || $campo === 'pago_matricula') {
            return $this->uploadPagoMatricula($userId, $file);
        }

        return $this->uploadOtherDocument($userId, $campo, $file);
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $roleName = optional(Auth::user()->role)->nombre;
        $allowedRoles = ['Administrador_sistema', 'Administrador de sistema', 'Rector', 'Coordinador Académico', 'Coordinador Academico'];

        $rules = [
            'user_id' => 'required|exists:users,id',
            'tipo_usuario' => 'required|in:nuevo,antiguo',
            'fecha_matricula' => 'required|date',
            'documento_identidad' => 'nullable|file|max:20480',
            'rh' => 'nullable|file|max:20480',
            'comprobante_pago' => 'nullable|file|max:20480',
            'certificado_medico' => 'nullable|file|max:20480',
            'certificado_notas' => 'required_if:tipo_usuario,antiguo|file|max:20480',
        ];

        if (in_array($roleName, $allowedRoles)) {
            $rules['estado'] = 'nullable|in:activo,inactivo,completado,suspendido,falta de documentacion';
        }

        $request->validate($rules);

        // Validación adicional para UPDATE: si el tipo de usuario es 'antiguo'
        // y no existe certificado_notas en la DB y no se está subiendo uno nuevo,
        // devolver error para forzar la carga del certificado.
        if ($request->isMethod('post') === false) {
            $tipo = $request->input('tipo_usuario');
            if ($tipo === 'antiguo') {
                $willDelete = $request->has('delete_certificado_notas');
                $hasExisting = !empty($matricula->certificado_notas);
                $isUploading = $request->hasFile('certificado_notas');

                if (($willDelete && !$isUploading) || (!$hasExisting && !$isUploading)) {
                    return back()->withErrors(['certificado_notas' => 'El certificado de notas es obligatorio para usuarios antiguos.'])
                                 ->withInput();
                }
            }
        }

        $userId = (int) $request->user_id;
        $tipo_usuario = $request->input('tipo_usuario');

        $campos = ['documento_identidad', 'rh', 'comprobante_pago', 'certificado_medico'];
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

        $matricula = new Matricula();
        $matricula->user_id = $userId;
        $matricula->fecha_matricula = $request->fecha_matricula;
        $matricula->tipo_usuario = $tipo_usuario ?? null;
        // Guardar el nombre base del curso seleccionado en la matrícula (informativo)
        // Sólo hacerlo si la columna existe en la base de datos (migración no aplicada aún evita error)
        try {
            if (Schema::hasColumn('matriculas', 'curso_nombre')) {
                $matricula->curso_nombre = $request->input('curso_nombre') ?: null;
            }
        } catch (\Throwable $e) {
            // Si por alguna razón no se puede comprobar el esquema, omitimos el campo para evitar excepción
            logger()->warning('No se pudo comprobar columna curso_nombre al crear matrícula: ' . $e->getMessage());
        }
        $matricula->documento_identidad = $rutas['documento_identidad'] ?? null;
        $matricula->rh = $rutas['rh'] ?? null;
        $matricula->comprobante_pago = $rutas['comprobante_pago'] ?? null;
        $matricula->certificado_medico = $rutas['certificado_medico'] ?? null;
        $matricula->certificado_notas = $rutas['certificado_notas'] ?? null;
        // Permitir que solo roles autorizados establezcan manualmente el estado
        if (in_array($roleName, $allowedRoles) && $request->filled('estado')) {
            $matricula->estado = $request->input('estado');
        }
        $matricula->save();

        // Si se subió un comprobante de pago, registrar su metadata en la tabla de audit
        if (!empty($rutas['comprobante_pago'])) {
            try {
                $mc = MatriculaComprobante::create([
                    'matricula_id' => $matricula->id,
                    'filename' => basename($rutas['comprobante_pago']),
                    'path' => $rutas['comprobante_pago'],
                    'original_name' => $request->hasFile('comprobante_pago') ? $request->file('comprobante_pago')->getClientOriginalName() : null,
                    'uploaded_by' => Auth::id(),
                ]);

                // Crear notificación para el acudiente del estudiante (si existe)
                try {
                    $studentUser = User::find($matricula->user_id);
                    $destUserId = $studentUser && $studentUser->acudiente_id ? $studentUser->acudiente_id : $matricula->user_id;

                    Notificacion::create([
                        'usuario_id' => $destUserId,
                        'titulo' => 'Comprobante de matrícula subido',
                        'mensaje' => 'Se ha subido un comprobante de matrícula: ' . ($mc->original_name ?? $mc->filename),
                        'leida' => false,
                        'fecha' => now(),
                        'tipo' => 'comprobante_matricula',
                    ]);
                } catch (\Throwable $e) {
                    \Log::warning('store(): no se pudo crear notificación por comprobante', ['error' => $e->getMessage()]);
                }
            } catch (\Exception $e) {
                \Log::warning('store(): no se pudo crear MatriculaComprobante', ['error' => $e->getMessage()]);
            }
        }

        // Nota: no se asigna automáticamente `curso_id` aquí.
        // La selección de curso en el formulario de matrícula sirve solo
        // para mostrar una preferencia/base; la asignación real se debe
        // realizar desde el módulo de Asignaciones.

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
     * Devuelve los cursos existentes que coinciden con una base (ej: "Primero" -> "Primero A", "Primero B").
     * Usado por AJAX en el formulario de matrícula para mostrar las opciones informativas.
     */
    public function cursosPorBase($base)
    {
        $base = trim(urldecode($base));
        $items = \App\Models\Curso::where('nombre', 'LIKE', $base . ' %')
            ->orWhere('nombre', $base)
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        return response()->json($items);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Matricula $matricula)
    {
        // Obtener dinámicamente el id del rol 'Estudiante'
        $studentRole = RolesModel::where('nombre', 'Estudiante')->first();
        if ($studentRole) {
            $q = trim($request->get('q', ''));
            if ($q !== '' || $request->ajax() || $request->wantsJson()) {
                if ($q === '') {
                    return response()->json(['data' => []]);
                }


                $students = User::where('roles_id', $studentRole->id)
                    ->where('document_number', 'like', "%{$q}%")
                    ->select('id','name','first_name','second_name','first_last','second_last','document_number','document_type','email','celular')
                    ->orderBy('document_number')
                    ->limit(50)
                    ->get();

                return response()->json(['data' => $students]);
            }

            $students = User::where('roles_id', $studentRole->id)->get();
        } else {
            $students = collect();
        }

        // También enviar las bases de curso (mismo criterio que en create)
        $rawCursos = \App\Models\Curso::orderBy('nombre')->pluck('nombre');
        $baseCursos = [];
        foreach ($rawCursos as $nombre) {
            if (preg_match('/^(.*?)[\s\-\(\[]+([A-Za-zÁÉÍÓÚÑáéíóúñ])$/u', trim($nombre), $m)) {
                $base = trim($m[1]);
                if ($base === '') {
                    $base = $nombre;
                }
                if (!in_array($base, $baseCursos, true)) {
                    $baseCursos[] = $base;
                }
            }
        }

        // Calcular la base del curso actual de la matrícula (si existe)
        $currentCursoBase = null;
        if ($matricula->curso && !empty($matricula->curso->nombre)) {
            $nombre = $matricula->curso->nombre;
            if (preg_match('/^(.*?)[\s\-\(\[]+([A-Za-zÁÉÍÓÚÑáéíóúñ])$/u', trim($nombre), $m)) {
                $currentCursoBase = trim($m[1]);
            } else {
                $currentCursoBase = $nombre;
            }
        }

        return view('matriculas.edit', compact('matricula', 'students', 'baseCursos', 'currentCursoBase'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $matricula = Matricula::findOrFail($id);

        $roleName = optional(Auth::user()->role)->nombre;
        $allowedRoles = ['Administrador_sistema', 'Administrador de sistema', 'Rector', 'Coordinador Académico', 'Coordinador Academico'];

        $rules = [
            'user_id' => 'required|exists:users,id',
            'fecha_matricula' => 'required|date',
            'documento_identidad' => 'nullable|file|max:20480',
            'rh' => 'nullable|file|max:20480',
            'comprobante_pago' => 'nullable|file|max:20480',
            'certificado_medico' => 'nullable|file|max:20480',
            'certificado_notas' => 'nullable|file|max:20480',
        ];

        if (in_array($roleName, $allowedRoles)) {
            $rules['estado'] = 'nullable|in:activo,inactivo,completado,suspendido,falta de documentacion';
        }

        $request->validate($rules);

        $documentos = [
            'documento_identidad',
            'rh',
            'comprobante_pago',
            'certificado_medico',
            'certificado_notas'
        ];

        // Variables para capturar datos del comprobante si se sube durante la actualización
        $uploadedComprobantePath = null;
        $uploadedComprobanteOriginal = null;

        $deletedAny = false;
        $deletedList = [];

        foreach ($documentos as $campo) {
            if ($request->has("delete_$campo")) {
                if ($matricula->$campo) {
                    try {
                        Storage::disk('ftp_matriculas')->delete($matricula->$campo);
                    } catch (\Exception $e) {
                        \Log::warning('update(): error borrando archivo desde delete button', ['campo' => $campo, 'error' => $e->getMessage()]);
                    }
                    $matricula->$campo = null;
                    $deletedAny = true;
                    $deletedList[] = $campo;
                }
            }

            if ($request->hasFile($campo)) {
                // Para comprobante_pago NO eliminamos históricos ni el archivo existente.
                if ($campo !== 'comprobante_pago') {
                    if ($matricula->$campo) {
                        try {
                            Storage::disk('ftp_matriculas')->delete($matricula->$campo);
                        } catch (\Exception $e) {
                            \Log::warning('update(): error borrando archivo previo antes de reemplazar', ['campo' => $campo, 'error' => $e->getMessage()]);
                        }
                    }
                }

                $uploaded = $this->uploadByFieldName((int)$request->user_id, $campo, $request->file($campo));
                if ($uploaded) {
                    $matricula->$campo = $uploaded;
                    if ($campo === 'comprobante_pago') {
                        $uploadedComprobantePath = $uploaded;
                        $uploadedComprobanteOriginal = $request->hasFile($campo) ? $request->file($campo)->getClientOriginalName() : null;
                    }
                }
            }
        }

        $matricula->user_id = $request->user_id;
        $matricula->fecha_matricula = $request->fecha_matricula;
        $matricula->tipo_usuario = $request->tipo_usuario;
        // Mantener la preferencia/base del curso originalmente seleccionada
        try {
            if (Schema::hasColumn('matriculas', 'curso_nombre')) {
                $matricula->curso_nombre = $request->input('curso_nombre') ?: $matricula->curso_nombre;
            }
        } catch (\Throwable $e) {
            logger()->warning('No se pudo comprobar columna curso_nombre al actualizar matrícula: ' . $e->getMessage());
        }

        // Solo roles autorizados pueden cambiar el estado manualmente
        if (in_array($roleName, $allowedRoles) && $request->filled('estado')) {
            $matricula->estado = $request->input('estado');
        }

        $matricula->save();

        // Si se subió un comprobante durante la actualización, registrar metadata
        if (!empty($uploadedComprobantePath)) {
            try {
                $mc = MatriculaComprobante::create([
                    'matricula_id' => $matricula->id,
                    'filename' => basename($uploadedComprobantePath),
                    'path' => $uploadedComprobantePath,
                    'original_name' => $uploadedComprobanteOriginal,
                    'uploaded_by' => Auth::id(),
                ]);

                // Notificar al acudiente del estudiante cuando se sube un comprobante
                try {
                    $studentUser = User::find($matricula->user_id);
                    $destUserId = $studentUser && $studentUser->acudiente_id ? $studentUser->acudiente_id : $matricula->user_id;

                    Notificacion::create([
                        'usuario_id' => $destUserId,
                        'titulo' => 'Comprobante de matrícula subido',
                        'mensaje' => 'Se ha subido un comprobante de matrícula: ' . ($mc->original_name ?? $mc->filename),
                        'leida' => false,
                        'fecha' => now(),
                    ]);
                } catch (\Throwable $e) {
                    \Log::warning('update(): no se pudo crear notificación por comprobante', ['error' => $e->getMessage()]);
                }
            } catch (\Exception $e) {
                \Log::warning('update(): no se pudo crear MatriculaComprobante', ['error' => $e->getMessage()]);
            }
        }

        // Nota: no se asigna automáticamente `curso_id` en la actualización.
        // La asignación se realiza desde el módulo de Asignaciones cuando corresponda.

        // Si borramos al menos un archivo, permanecer en la misma página de edición
        if ($deletedAny) {
            $msg = 'Documento eliminado correctamente.';
            if (count($deletedList) > 1) {
                $msg = 'Documentos eliminados correctamente.';
            }
            return redirect()->route('matriculas.edit', $matricula->id)->with('success', $msg);
        }

        return redirect()->route('matriculas.index')->with('success', 'Matrícula actualizada correctamente.');
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Matricula $matricula)
    {
        $userId = $matricula->user_id;

        // Borrar archivos referenciados en el modelo si existen
        $campos = ['documento_identidad', 'rh', 'comprobante_pago', 'certificado_medico', 'certificado_notas'];
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
     * Adaptado para funcionar con archivos almacenados en la raíz del disco FTP
     * con nombres prefijados (ej: ID_documento.pdf).
     */
    public function archivo(Matricula $matricula, $campo)
    {
        // 1. Lista de campos permitidos (incluyendo el nuevo 'comprobante_pago')
        $allowed = ['documento_identidad', 'rh', 'certificado_medico', 'certificado_notas', 'comprobante_pago'];
        if (!in_array($campo, $allowed)) {
            Log::warning('Matricula archivo(): campo no permitido', ['campo' => $campo, 'matricula_id' => $matricula->id]);
            abort(404);
        }

        // 2. Obtener la ruta desde la base de datos
        $path = $matricula->$campo;
        if (!$path) {
            Log::warning('Matricula archivo(): ruta vacía en BD', ['campo' => $campo, 'matricula_id' => $matricula->id]);
            abort(404);
        }

        $disk = Storage::disk('ftp_matriculas');
        $basename = basename($path); // Nombre del archivo, ej: "ID_12345678.pdf"

        // 3. ESTRATEGIA DE BÚSQUEDA OPTIMIZADA
        // Dado que los archivos ahora se guardan en la raíz, la búsqueda debe ser directa.

        // Intento A: Verificar si la ruta exacta de la BD existe.
        if ($disk->exists($path)) {
            Log::info('Matricula archivo(): encontrado por ruta exacta', ['matricula_id' => $matricula->id, 'path' => $path]);
            return $this->serveFileFromDisk($disk, $path);
        }

        // Intento B: La ruta de la BD podría ser incorrecta (ej: apunta a una carpeta vieja).
        // Lo más probable es que el archivo esté en la raíz con el mismo nombre.
        Log::info('Matricula archivo(): ruta exacta no encontrada, buscando en raíz por basename', [
            'matricula_id' => $matricula->id,
            'campo' => $campo,
            'ruta_bd' => $path,
            'basename' => $basename
        ]);

        try {
            // Listar archivos solo en la raíz ('') para mayor eficiencia.
            $allRootFiles = $disk->allFiles('');
            $found = null;
            foreach ($allRootFiles as $candidate) {
                if (strtolower(basename($candidate)) === strtolower($basename)) {
                    $found = $candidate;
                    break;
                }
            }

            if ($found) {
                Log::info('Matricula archivo(): encontrado en raíz por basename', ['matricula_id' => $matricula->id, 'ruta_encontrada' => $found]);
                return $this->serveFileFromDisk($disk, $found);
            }
        } catch (\Exception $e) {
            Log::error('Matricula archivo(): error listando archivos en raíz para fallback', ['error' => $e->getMessage()]);
        }

        // Si no se encuentra en ningún lugar, registrar el error y abortar.
        Log::error('Matricula archivo(): archivo no encontrado después de todos los intentos', [
            'matricula_id' => $matricula->id,
            'campo' => $campo,
            'ruta_bd' => $path,
            'basename' => $basename
        ]);
        abort(404);
    }

    /**
     * Validar o anular la validación del pago (solo tesorero).
     */
    public function validarPago(Request $request, Matricula $matricula)
    {
        $user = Auth::user();
        $roleName = optional($user->role)->nombre;

        // Aceptar varias variantes del nombre de rol 'tesorero'
        $allowed = ['tesorero', 'Tesorero', 'tesorero '];
        if (! $user || ! in_array($roleName, $allowed, true)) {
            abort(403, 'Acción no autorizada');
        }

        $action = $request->input('validar') ? true : false;

        $matricula->pago_validado = (bool) $action;
        $matricula->pago_validado_por = $action ? $user->id : null;
        $matricula->pago_validado_at = $action ? now() : null;
        $matricula->save();

        return redirect()->back()->with('success', $action ? 'Pago validado por Tesorería.' : 'Validación de pago anulada.');
    }

    /**
     * Servir un comprobante específico por nombre (solo tesorero puede ver todos).
     */
    public function comprobanteFile(Request $request, Matricula $matricula, $filename)
    {
        $user = Auth::user();
        $roleName = optional($user->role)->nombre ?? '';

        $isTesorero = in_array(strtolower($roleName), ['tesorero', 'tesorero '], true) || stripos($roleName, 'tesor') !== false;

        $basename = basename($filename);

        // Si no es tesorero, solo puede acceder al último comprobante (almacenado en DB)
        if (! $isTesorero) {
            $expected = basename($matricula->comprobante_pago ?? '');
            if ($basename !== $expected) {
                abort(403, 'Acceso no autorizado al comprobante solicitado');
            }
        }

        $disk = Storage::disk('ftp_matriculas');
        try {
            $all = [];
            try { $all = $disk->allFiles(''); } catch (\Exception $e) { $all = []; }
            try { $all = array_merge($all, $disk->allFiles('estudiante')); } catch (\Exception $e) { /* ignore */ }

            $found = null;
            foreach ($all as $candidate) {
                if (strtolower(basename($candidate)) === strtolower($basename)) {
                    $found = $candidate;
                    break;
                }
            }

            if ($found) {
                return $this->serveFileFromDisk($disk, $found);
            }
        } catch (\Exception $e) {
            \Log::error('comprobanteFile: error buscando archivo', ['error' => $e->getMessage()]);
        }

        abort(404, 'Comprobante no encontrado');
    }

    /**
     * Función auxiliar para servir el archivo una vez encontrada su ruta.
     * Encapsula la lógica de streaming, MIME types y cabeceras.
     */
    private function serveFileFromDisk($disk, string $path)
    {
        $filename = basename($path);
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
        ];
        $mime = $mimeTypes[$ext] ?? 'application/octet-stream';

        // Intentar obtener un stream para una entrega eficiente de archivos grandes
        try {
            $stream = $disk->readStream($path);
            if ($stream) {
                $headers = [
                    'Content-Type' => $mime,
                    'Content-Disposition' => 'inline; filename="' . $filename . '"',
                    'Cache-Control' => 'private, max-age=0, no-cache',
                ];
                return response()->stream(function () use ($stream) {
                    fpassthru($stream);
                    if (is_resource($stream)) {
                        @fclose($stream);
                    }
                }, 200, $headers);
            }
        } catch (\Exception $e) {
            Log::warning('serveFileFromDisk: readStream falló, intentando fallback get()', ['path' => $path, 'error' => $e->getMessage()]);
        }

        // Fallback: Si el stream falla, leer el archivo completo en memoria.
        // Menos eficiente para archivos grandes, pero más robusto.
        try {
            $content = $disk->get($path);
            $headers = [
                'Content-Type' => $mime,
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
                'Cache-Control' => 'private, max-age=0, no-cache',
            ];
            return response($content, 200, $headers);
        } catch (\Exception $e) {
            Log::error('serveFileFromDisk: fallback get() también falló', ['path' => $path, 'error' => $e->getMessage()]);
        }

        // Si todo falla, devolver un error 500
        abort(500, 'No se pudo leer el archivo desde el almacenamiento.');
    }
}