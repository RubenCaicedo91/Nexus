<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Mensaje;
use App\Models\Notificacion;
use App\Models\Circular;
use App\Models\Curso;
use App\Models\Matricula;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class ComunicacionController extends Controller
{
    // Vista principal del módulo
    public function index()
    {
        return view('comunicacion.index');
    }

    /**
     * Servir (visualizar/descargar inline) el archivo asociado a una circular.
     */
    public function archivoCircular(Request $request, $id)
    {
        $circ = Circular::findOrFail($id);
        if (! $circ->archivo) abort(404);

        $disk = \Illuminate\Support\Facades\Storage::disk('ftp_matriculas');
        $path = $circ->archivo;

        // Si la ruta exacta existe, servirla
        try {
            if ($disk->exists($path)) {
                return $this->serveFileFromDisk($disk, $path);
            }
        } catch (\Throwable $e) {
            logger()->warning('archivoCircular: error comprobando existencia en ftp_matriculas: ' . $e->getMessage());
        }

        // Fallback: buscar por basename en la raíz
        $basename = basename($path);
        try {
            $all = $disk->allFiles('');
            $found = null;
            foreach ($all as $candidate) {
                if (strtolower(basename($candidate)) === strtolower($basename)) { $found = $candidate; break; }
            }
            if ($found) {
                return $this->serveFileFromDisk($disk, $found);
            }
        } catch (\Throwable $e) {
            logger()->warning('archivoCircular: error buscando archivo en ftp_matriculas: ' . $e->getMessage());
        }

        // Si no se encontró, intentar servir desde disco public (fallback)
        try {
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
                $content = \Illuminate\Support\Facades\Storage::disk('public')->get($path);
                $mime = \Illuminate\Support\Facades\Storage::disk('public')->mimeType($path) ?? 'application/octet-stream';
                return response($content, 200, ['Content-Type' => $mime, 'Content-Disposition' => 'inline; filename="' . basename($path) . '"']);
            }
        } catch (\Throwable $e) {
            logger()->warning('archivoCircular: fallback public failed: ' . $e->getMessage());
        }

        abort(404);
    }

    /**
     * Adapter: servir un archivo por su nombre (basename) reutilizando el disco
     * que usa Matrículas (`ftp_matriculas`). Esta ruta acepta únicamente el
     * nombre del archivo y busca en la raíz y en la carpeta 'estudiante'.
     */
    public function archivoPorNombre(Request $request, $filename)
    {
        $basename = basename($filename);
        $disk = \Illuminate\Support\Facades\Storage::disk('ftp_matriculas');

        try {
            $all = [];
            try { $all = $disk->allFiles(''); } catch (\Throwable $e) { $all = []; }
            try { $all = array_merge($all, $disk->allFiles('estudiante')); } catch (\Throwable $e) { /* ignore */ }

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
        } catch (\Throwable $e) {
            logger()->warning('archivoPorNombre: error buscando en ftp_matriculas: ' . $e->getMessage());
        }

        // Fallback: buscar en disco 'public' bajo la carpeta 'circulares'
        try {
            $publicPath = 'circulares/' . $basename;
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($publicPath)) {
                $content = \Illuminate\Support\Facades\Storage::disk('public')->get($publicPath);
                $mime = \Illuminate\Support\Facades\Storage::disk('public')->mimeType($publicPath) ?? 'application/octet-stream';
                return response($content, 200, ['Content-Type' => $mime, 'Content-Disposition' => 'inline; filename="' . $basename . '"']);
            }
        } catch (\Throwable $e) {
            logger()->warning('archivoPorNombre: fallback public failed: ' . $e->getMessage());
        }

        abort(404);
    }

    private function serveFileFromDisk($disk, string $path)
    {
        $filename = basename($path);
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mimeTypes = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png'];
        $mime = $mimeTypes[$ext] ?? 'application/octet-stream';

        try {
            $stream = $disk->readStream($path);
            if ($stream) {
                $headers = ['Content-Type' => $mime, 'Content-Disposition' => 'inline; filename="' . $filename . '"', 'Cache-Control' => 'private, max-age=0, no-cache'];
                return response()->stream(function () use ($stream) { fpassthru($stream); if (is_resource($stream)) @fclose($stream); }, 200, $headers);
            }
        } catch (\Throwable $e) {
            logger()->warning('serveFileFromDisk: readStream failed: ' . $e->getMessage());
        }

        try {
            $content = $disk->get($path);
            $headers = ['Content-Type' => $mime, 'Content-Disposition' => 'inline; filename="' . $filename . '"', 'Cache-Control' => 'private, max-age=0, no-cache'];
            return response($content, 200, $headers);
        } catch (\Throwable $e) {
            logger()->error('serveFileFromDisk: fallback get failed: ' . $e->getMessage());
        }

        abort(500, 'No se pudo leer el archivo desde el almacenamiento.');
    }

    /**
     * Mostrar las respuestas (mensajes) asociadas a un grupo de notificaciones (group_key).
                // Guardar en el mismo disco/ruta que usa el módulo de Matrícula
                try {
                    $disk = \Illuminate\Support\Facades\Storage::disk('ftp_matriculas');
                    // Guardar en la raíz del disco (misma estrategia que comprobantes)
                    $stored = $disk->putFileAs('', $file, $filename);
                    $circular->archivo = $stored === false ? $filename : $stored;
                } catch (\Throwable $e) {
                    // Fallback: intentar guardar en disco 'public'
                    logger()->warning('guardarCircular: fallo guardando en ftp_matriculas, fallback a public: ' . $e->getMessage());
                    $path = $file->storeAs('circulares', $filename, 'public');
                    $circular->archivo = $path;
                }
    public function mostrarRespuestasGrupo(Request $request, $groupKey)
    {
        $user = Auth::user();
        if (! $user) abort(401);

        // Recuperar notificaciones del grupo
        $notifs = Notificacion::where('group_key', $groupKey)->get();
        if ($notifs->isEmpty()) {
            abort(404, 'Grupo no encontrado');
        }

        // Determinar si el usuario puede ver las respuestas: creador del grupo o rol administrativo
        $isCreator = $notifs->first()->creador_id && ((int)$notifs->first()->creador_id === (int)$user->id);
        $roleName = optional($user->role)->nombre ?? '';
        $roleLower = mb_strtolower($roleName);
        $allowedKeywords = ['administrador', 'rector', 'coordinador', 'tesorero'];
        $isAdminRole = false;
        foreach ($allowedKeywords as $k) {
            if (mb_stripos($roleLower, $k) !== false) { $isAdminRole = true; break; }
        }

        if (! $isCreator && ! $isAdminRole) {
            abort(403, 'No autorizado');
        }

        // Obtener ids de notificación del grupo
        $notifIds = $notifs->pluck('id')->all();

        // Recuperar mensajes que tengan notificacion_id dentro del grupo
        $mensajes = Mensaje::whereIn('notificacion_id', $notifIds)
            ->with(['remitente', 'destinatario'])
            ->orderBy('created_at', 'asc')
            ->get();

        // Datos de cabecera para la vista
        $groupInfo = [
            'group_key' => $groupKey,
            'titulo' => $notifs->first()->titulo ?? '',
            'mensaje' => $notifs->first()->mensaje ?? '',
            'creador_id' => $notifs->first()->creador_id ?? null,
            'created_at' => $notifs->first()->created_at ?? null,
        ];

        return view('comunicacion.notificaciones.respuestas', compact('mensajes', 'groupInfo'));
    }

    /**
     * Eliminar (lógicamente) todas las notificaciones de un grupo para los destinatarios.
     * Solo el creador del grupo puede ejecutar esta acción.
     */
    public function eliminarGrupoNotificaciones(Request $request, $groupKey)
    {
        $user = Auth::user();
        if (! $user) abort(401);

        $notifs = Notificacion::where('group_key', $groupKey)->get();
        if ($notifs->isEmpty()) {
            if ($request->wantsJson() || $request->ajax()) return response()->json(['error' => 'Grupo no encontrado'], 404);
            return back()->with('error', 'Grupo no encontrado');
        }

        // Solo el creador puede eliminar el grupo
        $creadorId = $notifs->first()->creador_id;
        if (! $creadorId || (int)$creadorId !== (int)$user->id) {
            if ($request->wantsJson() || $request->ajax()) return response()->json(['error' => 'No autorizado'], 403);
            return back()->with('error', 'No autorizado');
        }

        // Si la columna deleted_by_creador existe, marcarla; si no, eliminar físicamente
        if (\Illuminate\Support\Facades\Schema::hasColumn('notificaciones', 'deleted_by_creador')) {
            Notificacion::where('group_key', $groupKey)->update(['deleted_by_creador' => true]);
        } else {
            Notificacion::where('group_key', $groupKey)->delete();
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['message' => 'Grupo eliminado correctamente'], 200);
        }

        return redirect()->route('comunicacion.notificaciones')->with('success', 'Grupo de notificaciones eliminado correctamente');
    }
    // ---------------- Mensajes ----------------
    public function listarMensajes()
    {
        // Bandeja de entrada del usuario autenticado
        // Eager load remitente para mostrar nombre en la vista
        $mensajes = Mensaje::where('destinatario_id', Auth::id())
            ->where(function($q){ $q->whereNull('deleted_by_destinatario')->orWhere('deleted_by_destinatario', false); })
            ->with('remitente')->latest()->get();
        // Mensajes enviados por el usuario (para mostrar en el mismo panel)
        $mensajesEnviados = Mensaje::where('remitente_id', Auth::id())
            ->where(function($q){ $q->whereNull('deleted_by_remitente')->orWhere('deleted_by_remitente', false); })
            ->with('destinatario')->latest()->get();
        // Cargar roles para el formulario de envío (grupos)
        $roles = \App\Models\RolesModel::orderBy('nombre')->get();
        // Cargar lista inicial de usuarios (limitada) para evitar error en la vista
        $usuarios = \App\Models\User::orderBy('name')->limit(200)->get();

        return view('comunicacion.mensajes.index', compact('mensajes', 'mensajesEnviados', 'roles', 'usuarios'));
    }

    public function crearMensaje()
    {
        return view('comunicacion.mensajes.create');
    }

    public function guardarMensaje(Request $request)
    {
        $request->validate([
            'modo' => 'required|string|in:rol,todos',
            'asunto' => 'required|string|max:255',
            'contenido' => 'required|string',
            'rol_id' => 'nullable|integer',
            'usuarios_grupo' => 'nullable|array',
            'usuarios_grupo.*' => 'integer',
        ]);

        $modo = $request->modo;
        $destinatarios = [];

        // Generar clave de grupo para agrupar las notificaciones creadas en esta acción
        $groupKey = (string) \Illuminate\Support\Str::uuid();

        if ($modo === 'todos') {
            $destinatarios = \App\Models\User::pluck('id')->toArray();
        } elseif ($modo === 'rol') {
            if (! $request->rol_id) {
                return back()->withErrors(['rol_id' => 'Seleccione un grupo para enviar'])->withInput();
            }
                // Si se enviaron IDs específicos de usuarios del grupo, usarlos
                if ($request->filled('usuarios_grupo') && is_array($request->usuarios_grupo) && count($request->usuarios_grupo) > 0) {
                    $destinatarios = array_map('intval', $request->usuarios_grupo);
                } else {
                    $destinatarios = \App\Models\User::where('roles_id', $request->rol_id)->pluck('id')->toArray();
                }
        } else {
            return back()->withErrors(['modo' => 'Modo de envío inválido'])->withInput();
        }

        // Crear mensajes para cada destinatario
        foreach ($destinatarios as $destId) {
            if ($destId == Auth::id()) continue; // Evitar enviar mensaje a self

            Mensaje::create([
                'remitente_id' => Auth::id(),
                'destinatario_id' => $destId,
                'asunto' => $request->asunto,
                'contenido' => $request->contenido,
                'leido' => false,
            ]);
        }

        return redirect()->route('comunicacion.mensajes')->with('success', 'Mensaje(s) enviado(s) correctamente');
    }

    // Listar mensajes enviados por el remitente autenticado
    public function listarMensajesEnviados()
    {
        $mensajes = Mensaje::where('remitente_id', Auth::id())
            ->where(function($q){ $q->whereNull('deleted_by_remitente')->orWhere('deleted_by_remitente', false); })
            ->with('destinatario')->latest()->get();
        $roles = \App\Models\RolesModel::orderBy('nombre')->get();
        return view('comunicacion.mensajes.enviados', compact('mensajes', 'roles'));
    }

    // Mostrar detalle de un mensaje (remitente o destinatario pueden verlo)
    public function mostrarMensaje(\Illuminate\Http\Request $request, $id)
    {
        $mensaje = Mensaje::with(['remitente', 'destinatario'])->findOrFail($id);
        $userId = Auth::id();
        if ($mensaje->remitente_id !== $userId && $mensaje->destinatario_id !== $userId) {
            abort(403, 'No autorizado');
        }

        // Si el mensaje fue eliminado para este usuario, no permitir verlo
        if ($mensaje->remitente_id === $userId && ($mensaje->deleted_by_remitente ?? false)) {
            abort(404);
        }
        if ($mensaje->destinatario_id === $userId && ($mensaje->deleted_by_destinatario ?? false)) {
            abort(404);
        }

        // Si quien ve es el destinatario y no está leído, marcar como leído
        // Permitimos saltar el marcado automático cuando la solicitud incluye ?skip_mark=1
        $skipMark = $request->boolean('skip_mark');
        if ($mensaje->destinatario_id === $userId && ! $mensaje->leido && ! $skipMark) {
            $mensaje->leido = true;
            $mensaje->save();
        }

        // Si la petición solicita JSON (AJAX), devolver los datos como JSON para uso en modal
        if ($request->wantsJson() || $request->ajax()) {
            // Construir hilo: determinar root y recuperar todos los mensajes del hilo (root + replies)
            $rootId = $mensaje->parent_id ?: $mensaje->id;
            // Recuperar sólo los mensajes del hilo que no hayan sido eliminados para el usuario actual
            $thread = Mensaje::where(function($q) use ($rootId) {
                $q->where('id', $rootId)->orWhere('parent_id', $rootId);
            })->where(function($q) use ($userId) {
                $q->where(function($s) use ($userId){
                    $s->where('remitente_id', $userId)->where(function($x){ $x->whereNull('deleted_by_remitente')->orWhere('deleted_by_remitente', false); });
                })->orWhere(function($s) use ($userId){
                    $s->where('destinatario_id', $userId)->where(function($x){ $x->whereNull('deleted_by_destinatario')->orWhere('deleted_by_destinatario', false); });
                });
            })->with(['remitente', 'destinatario'])->orderBy('created_at', 'asc')->get();

            $threadData = $thread->map(function($m){
                return [
                    'id' => $m->id,
                    'remitente' => $m->remitente ? ['id' => $m->remitente->id, 'name' => $m->remitente->name] : null,
                    'destinatario' => $m->destinatario ? ['id' => $m->destinatario->id, 'name' => $m->destinatario->name] : null,
                    'asunto' => $m->asunto,
                    'contenido' => $m->contenido,
                    'leido' => (bool) $m->leido,
                    'created_at' => $m->created_at->toDateTimeString(),
                ];
            });

            return response()->json([
                'id' => $mensaje->id,
                'remitente' => $mensaje->remitente ? ['id' => $mensaje->remitente->id, 'name' => $mensaje->remitente->name] : null,
                'destinatario' => $mensaje->destinatario ? ['id' => $mensaje->destinatario->id, 'name' => $mensaje->destinatario->name] : null,
                'asunto' => $mensaje->asunto,
                'contenido' => $mensaje->contenido,
                'leido' => (bool) $mensaje->leido,
                'created_at' => $mensaje->created_at->toDateTimeString(),
                'thread' => $threadData,
            ]);
        }

        return view('comunicacion.mensajes.show', compact('mensaje'));
    }

    // Marcar como no leído (solo destinatario puede hacerlo)
    public function marcarNoLeido(\Illuminate\Http\Request $request, $id)
    {
        $mensaje = Mensaje::findOrFail($id);
        if ($mensaje->destinatario_id !== Auth::id()) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['error' => 'Solo el destinatario puede marcar como no leído'], 403);
            }
            return back()->with('error', 'Solo el destinatario puede marcar como no leído');
        }
        $mensaje->leido = false;
        $mensaje->save();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['message' => 'Mensaje marcado como no leído'], 200);
        }

        // Redirigir al detalle del mensaje para mantener una URL segura en el historial
        // Añadimos ?skip_mark=1 para evitar que mostrarMensaje vuelva a marcarlo como leído inmediatamente
        return redirect()->route('comunicacion.mensajes.show', ['id' => $id, 'skip_mark' => 1])->with('success', 'Mensaje marcado como no leído');
    }

    // Mostrar formulario de respuesta
    public function formResponder($id)
    {
        $mensaje = Mensaje::with('remitente')->findOrFail($id);
        $userId = Auth::id();
        if ($mensaje->remitente_id !== $userId && $mensaje->destinatario_id !== $userId) {
            abort(403, 'No autorizado');
        }
        return view('comunicacion.mensajes.responder', compact('mensaje'));
    }

    // Enviar respuesta al remitente original
    public function enviarRespuesta(Request $request, $id)
    {
        $original = Mensaje::findOrFail($id);
        $userId = Auth::id();
        if ($original->remitente_id !== $userId && $original->destinatario_id !== $userId) {
            abort(403, 'No autorizado');
        }

        $request->validate([
            'asunto' => 'required|string|max:255',
            'contenido' => 'required|string',
        ]);

        // Destinatario de la respuesta: si yo era destinatario original, enviamos al remitente; si yo era remitente, enviamos al destinatario original
        $to = ($original->destinatario_id === $userId) ? $original->remitente_id : $original->destinatario_id;

        // Determinar root del hilo: si el original tiene parent_id, usarlo; si no, usar el original
        $rootId = $original->parent_id ?: $original->id;

        Mensaje::create([
            'remitente_id' => $userId,
            'destinatario_id' => $to,
            'asunto' => $request->asunto,
            'contenido' => $request->contenido,
            'leido' => false,
            'parent_id' => $rootId,
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['message' => 'Respuesta enviada correctamente'], 200);
        }

        return redirect()->route('comunicacion.mensajes.enviados')->with('success', 'Respuesta enviada correctamente');
    }

    // Eliminar mensaje (solo remitente puede eliminar)
    public function eliminarMensaje(\Illuminate\Http\Request $request, $id)
    {
        $mensaje = Mensaje::findOrFail($id);
        $userId = Auth::id();
        // Permitir "eliminar" si es remitente o destinatario; el borrado será lógico por usuario
        if ($mensaje->remitente_id !== $userId && $mensaje->destinatario_id !== $userId) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['error' => 'No autorizado'], 403);
            }
            return back()->with('error', 'No autorizado');
        }

        // Marcar la bandera correspondiente
        if ($mensaje->remitente_id === $userId) {
            $mensaje->deleted_by_remitente = true;
        }
        if ($mensaje->destinatario_id === $userId) {
            $mensaje->deleted_by_destinatario = true;
        }

        // Si ambos borraron, eliminar físicamente
        if (($mensaje->deleted_by_remitente ?? false) && ($mensaje->deleted_by_destinatario ?? false)) {
            $mensaje->delete();
        } else {
            $mensaje->save();
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['message' => 'Mensaje eliminado'], 200);
        }

        return redirect()->route('comunicacion.mensajes')->with('success', 'Mensaje eliminado');
    }

    // ---------------- Notificaciones ----------------
    public function listarNotificaciones()
    {
        $notificacionesQuery = Notificacion::where('usuario_id', Auth::id());
        if (Schema::hasColumn('notificaciones', 'deleted_by_creador')) {
            $notificacionesQuery->where(function($q){ $q->whereNull('deleted_by_creador')->orWhere('deleted_by_creador', false); });
        }
        $notificaciones = $notificacionesQuery->latest()->get();
        // roles para formulario de envío
        $roles = \App\Models\RolesModel::orderBy('nombre')->get();
        // identificar id del rol Estudiante para comportamiento especial en la vista
        $rolEstudiante = \App\Models\RolesModel::where('nombre', 'Estudiante')->first();
        $estudianteRoleId = $rolEstudiante ? $rolEstudiante->id : null;
        // cargar cursos (se usan sólo cuando rol Estudiante está seleccionado)
        $cursos = Curso::orderBy('nombre')->get();

        // notificaciones enviadas por el usuario (agrupar por group_key)
        $sentGroupsQuery = Notificacion::where('creador_id', Auth::id())
            ->select('group_key','titulo','mensaje','created_at')
            ->whereNotNull('group_key');

        if (Schema::hasColumn('notificaciones', 'deleted_by_creador')) {
            $sentGroupsQuery->where(function($q){ $q->whereNull('deleted_by_creador')->orWhere('deleted_by_creador', false); });
        }

        $sentGroups = $sentGroupsQuery->orderByDesc('created_at')->distinct()->get();

        return view('comunicacion.notificaciones.index', compact('notificaciones', 'roles', 'estudianteRoleId', 'cursos', 'sentGroups'));
    }

    public function marcarNotificacionLeida($id)
    {
        $notif = Notificacion::findOrFail($id);
        $notif->leida = true;
        $notif->save();

        return back()->with('success', 'Notificación marcada como leída');
    }

    /**
     * Mostrar detalle de notificación. Si la petición es AJAX/JSON, devolver JSON
     * para mostrar en modal. Si no, renderizar una vista simple.
     */
    public function mostrarNotificacion(Request $request, $id)
    {
        $notif = Notificacion::with(['creador' => function($q){ $q->select('id','name'); }])->findOrFail($id);

        $user = Auth::user();
        if (! $user) abort(401);

        // Sólo el destinatario puede ver su notificación
        if ((int)$notif->usuario_id !== (int)$user->id) {
            abort(403);
        }

        // Marcar como leída si no lo está
        if (! $notif->leida) {
            $notif->leida = true;
            $notif->save();
        }

        $creador = null;
        if ($notif->creador_id) {
            try { $creador = \App\Models\User::select('id','name')->find($notif->creador_id); } catch (\Throwable $e) { $creador = null; }
        }

        $canReply = false;
        if ((int)$notif->usuario_id === (int)$user->id) {
            // Si la notificación está marcada como solo lectura, no permitir respuesta
            if (! empty($notif->solo_lectura)) {
                $canReply = false;
            } else {
                $roleName = optional($user->role)->nombre ?? '';
                $isAcudiente = stripos($roleName, 'acudiente') !== false;

                // Si es notificación de pago de matrícula, los acudientes NO pueden responder
                if ($notif->tipo === 'pago_matricula' && $isAcudiente) {
                    $canReply = false;
                } else {
                    if (! $notif->solo_acudiente_responde) {
                        $canReply = true;
                    } else {
                        if ($isAcudiente) $canReply = true;
                    }
                }
            }
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'id' => $notif->id,
                'titulo' => $notif->titulo,
                'mensaje' => $notif->mensaje,
                'fecha' => $notif->fecha,
                'leida' => (bool)$notif->leida,
                'creador' => $creador ? ['id' => $creador->id, 'name' => $creador->name] : null,
                'canReply' => $canReply,
                'replyUrl' => $canReply ? route('comunicacion.notificaciones.responder.form', $notif->id) : null,
            ]);
        }

        return view('comunicacion.notificaciones.show', compact('notif', 'creador', 'canReply'));
    }

    // Mostrar formulario para responder a una notificación (solo destinatario)
    public function formResponderNotificacion($id)
    {
        $notif = Notificacion::findOrFail($id);
        $user = Auth::user();
        if (! $user) abort(403);

        // Solo el destinatario original puede responder
        if ((int)$notif->usuario_id !== (int)$user->id) {
            abort(403, 'Solo el destinatario puede responder esta notificación');
        }

        // Si la notificación es de pago de matrícula, y quien intenta abrir es un acudiente, no permitir
        $roleName = optional($user->role)->nombre ?? '';
        $isAcudiente = stripos($roleName, 'acudiente') !== false;
        if (! empty($notif->solo_lectura)) {
            abort(403, 'Esta notificación no acepta respuestas');
        }

        if ($notif->tipo === 'pago_matricula' && $isAcudiente) {
            abort(403, 'Esta notificación no acepta respuestas');
        }

        // Si la notificación está marcada como "solo acudiente puede responder", validar rol
        if ($notif->solo_acudiente_responde) {
            if (! $isAcudiente) {
                abort(403, 'Solo el acudiente puede responder esta notificación');
            }
        }

        return view('comunicacion.notificaciones.responder', compact('notif'));
    }

    // Enviar la respuesta (crea un Mensaje dirigido al creador de la notificación)
    public function enviarRespuestaNotificacion(Request $request, $id)
    {
        $notif = Notificacion::findOrFail($id);
        $user = Auth::user();
        if (! $user) abort(403);

        if ((int)$notif->usuario_id !== (int)$user->id) {
            abort(403, 'Solo el destinatario puede responder esta notificación');
        }

        // Si la notificación es de pago y quien intenta enviar es un acudiente, no aceptar
        $roleName = optional($user->role)->nombre ?? '';
        $isAcudiente = stripos($roleName, 'acudiente') !== false;
        if (! empty($notif->solo_lectura)) {
            abort(403, 'Esta notificación no acepta respuestas');
        }

        if ($notif->tipo === 'pago_matricula' && $isAcudiente) {
            abort(403, 'Esta notificación no acepta respuestas');
        }

        if ($notif->solo_acudiente_responde) {
            if (! $isAcudiente) {
                abort(403, 'Solo el acudiente puede responder esta notificación');
            }
        }

        $request->validate([
            'asunto' => 'required|string|max:255',
            'contenido' => 'required|string',
        ]);

        if (! $notif->creador_id) {
            return back()->withErrors(['creador' => 'No se puede responder: remitente desconocido']);
        }

        // Crear mensaje hacia el creador de la notificación
        Mensaje::create([
            'remitente_id' => $user->id,
            'destinatario_id' => $notif->creador_id,
            'asunto' => $request->asunto,
            'contenido' => $request->contenido,
            'leido' => false,
            'notificacion_id' => $notif->id,
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['message' => 'Respuesta enviada correctamente'], 200);
        }

        return redirect()->route('comunicacion.notificaciones')->with('success', 'Respuesta enviada correctamente');
    }

    public function guardarNotificacion(Request $request)
    {
        $request->validate([
            'modo' => 'required|string|in:rol,todos',
            'titulo' => 'required|string|max:255',
            'mensaje' => 'required|string',
            'rol_id' => 'nullable|integer',
            'usuarios' => 'nullable|array',
            'usuarios.*' => 'integer',
            'curso_id' => 'nullable|integer',
            'solo_acudiente_responde' => 'nullable|boolean',
        ]);

        $modo = $request->modo;
        $destinatarios = [];

        // Generar clave de grupo para agrupar las notificaciones creadas en esta acción
        $groupKey = (string) \Illuminate\Support\Str::uuid();

        if ($modo === 'todos') {
            $destinatarios = \App\Models\User::pluck('id')->toArray();
        } elseif ($modo === 'rol') {
            // Si se enviaron IDs específicos de usuarios del grupo, usarlos
            if ($request->filled('usuarios') && is_array($request->usuarios) && count($request->usuarios) > 0) {
                $destinatarios = array_map('intval', $request->usuarios);
            } else {
                if (! $request->rol_id) {
                    return back()->withErrors(['rol_id' => 'Seleccione un grupo para enviar'])->withInput();
                }

                // Comportamiento especial para Estudiantes: si se envía curso_id, seleccionar estudiantes de ese curso
                $rolEstudiante = \App\Models\RolesModel::where('nombre', 'Estudiante')->first();
                $estudianteRoleId = $rolEstudiante ? (int)$rolEstudiante->id : null;

                if ($estudianteRoleId && (int)$request->rol_id === $estudianteRoleId && $request->filled('curso_id')) {
                    // Obtener estudiantes matriculados en el curso
                    $cursoId = (int)$request->curso_id;
                    $matriculas = Matricula::where('curso_id', $cursoId)->with('user')->get();
                    $userIds = $matriculas->pluck('user.id')->filter()->unique()->values()->all();

                    // Añadir también los acudientes registrados de esos estudiantes
                    $acudienteIds = User::whereIn('id', $userIds)->pluck('acudiente_id')->filter()->unique()->values()->all();

                    $destinatarios = array_values(array_unique(array_merge($userIds, $acudienteIds)));
                } else {
                    $destinatarios = \App\Models\User::where('roles_id', $request->rol_id)->pluck('id')->toArray();
                }
            }
        } else {
            return back()->withErrors(['modo' => 'Modo de envío inválido'])->withInput();
        }

        // Si se seleccionaron usuarios individuales y (algunos) son estudiantes, también incluir sus acudientes
        $finalDestinatarios = [];
        if (is_array($destinatarios)) {
            foreach ($destinatarios as $d) {
                $finalDestinatarios[] = (int)$d;
                // si el destinatario es un estudiante, incluir su acudiente
                $u = User::find($d);
                if ($u && optional($u->role)->nombre === 'Estudiante') {
                    if ($u->acudiente_id) $finalDestinatarios[] = (int)$u->acudiente_id;
                }
            }
        }

        $finalDestinatarios = array_values(array_unique($finalDestinatarios));

        foreach ($finalDestinatarios as $destId) {
            // Evitar enviar notificación a self
            if ($destId == Auth::id()) continue;

            Notificacion::create([
                'usuario_id' => $destId,
                'titulo' => $request->titulo,
                'mensaje' => $request->mensaje,
                'leida' => false,
                'fecha' => now(),
                'creador_id' => Auth::id(),
                'solo_acudiente_responde' => $request->boolean('solo_acudiente_responde', false),
                'group_key' => $groupKey,
                'deleted_by_creador' => false,
            ]);
        }

        return redirect()->route('comunicacion.notificaciones')->with('success', 'Notificación(es) enviada(s) correctamente');
    }

    /**
     * Endpoint JSON: devuelve usuarios (estudiantes) matriculados en un curso.
     * Parámetros opcionales: q para buscar por nombre o documento.
     */
    public function estudiantesPorCurso(Request $request, $cursoId)
    {
        if (! Auth::check()) {
            return response()->json(['data' => []], 401);
        }

        $q = trim($request->get('q', ''));

        $query = Matricula::where('curso_id', $cursoId)->with('user');

        $matriculas = $query->get();

        $users = $matriculas->map(function($m){
            if (! $m->user) return null;
            return [
                'id' => $m->user->id,
                'name' => $m->user->name,
                'document_number' => $m->user->document_number ?? null,
            ];
        })->filter();

        if ($q !== '') {
            $qLower = mb_strtolower($q);
            $users = $users->filter(function($u) use ($qLower) {
                return mb_stripos($u['name'], $qLower) !== false || (isset($u['document_number']) && mb_stripos($u['document_number'], $qLower) !== false);
            });
        }

        $users = $users->values()->take(200);

        return response()->json(['data' => $users]);
    }

    // ---------------- Circulares ----------------
    public function listarCirculares()
    {
        $circulares = Circular::latest()->get();
        // Pasar roles para el formulario de envío (grupos)
        $roles = \App\Models\RolesModel::orderBy('nombre')->get();
        // identificar id del rol Estudiante para comportamiento especial en la vista
        $rolEstudiante = \App\Models\RolesModel::where('nombre', 'Estudiante')->first();
        $estudianteRoleId = $rolEstudiante ? $rolEstudiante->id : null;
        // cargar cursos (se usan sólo cuando rol Estudiante está seleccionado)
        $cursos = Curso::orderBy('nombre')->get();

        $user = Auth::user();
        // determinar si el usuario puede enviar circulares (palabras clave en el nombre del rol)
        $canSend = false;
        if ($user) {
            $roleName = optional($user->role)->nombre ?? '';
            $roleLower = mb_strtolower($roleName);
            $allowedKeywords = ['rector','administrador','coordinador','tesorero'];
            foreach ($allowedKeywords as $k) { if (mb_stripos($roleLower, $k) !== false) { $canSend = true; break; } }
        }

        $myCirculares = collect();
        $othersCirculares = collect();
        // Evitar consultas si la columna creador_id no existe (migraciones no ejecutadas)
        if ($canSend && $user && \Illuminate\Support\Facades\Schema::hasColumn('circulars', 'creador_id')) {
            $myCirculares = Circular::where('creador_id', $user->id)->orderByDesc('created_at')->get();
            // circulares creadas por otros usuarios que también pueden enviar
            $allowedUsers = \App\Models\User::whereHas('role', function($q) use ($allowedKeywords){
                foreach ($allowedKeywords as $k) {
                    $q->orWhere('nombre', 'like', "%{$k}%");
                }
            })->pluck('id')->all();

            $othersCirculares = Circular::whereIn('creador_id', $allowedUsers)->where('creador_id', '<>', $user->id)->orderByDesc('created_at')->get();
        }

        // determinar si el usuario puede eliminar (solo Rector y Administrador del sistema)
        $canDelete = false;
        if ($user) {
            $rName = optional($user->role)->nombre ?? '';
            $rLower = mb_strtolower($rName);
            if (mb_stripos($rLower, 'rector') !== false || mb_stripos($rLower, 'administrador') !== false) {
                $canDelete = true;
            }
        }

        return view('comunicacion.circulares.index', compact('circulares', 'roles', 'estudianteRoleId', 'cursos', 'canSend', 'myCirculares', 'othersCirculares', 'canDelete'));
    }

    public function crearCircular()
    {
        $user = Auth::user();
        // sólo permitir a usuarios con roles autorizados
        $roleName = optional($user->role)->nombre ?? '';
        $roleLower = mb_strtolower($roleName);
        $allowedKeywords = ['rector','administrador','coordinador','tesorero'];
        $canSend = false; foreach ($allowedKeywords as $k) { if (mb_stripos($roleLower, $k) !== false) { $canSend = true; break; } }
        if (! $canSend) abort(403);
        return view('comunicacion.circulares.create');
    }

    public function guardarCircular(Request $request)
    {
        // Accept same envio modes as notificaciones (rol|todos) and optionally specific usuarios[] when modo=rol
        $request->validate([
            'modo' => 'required|string|in:rol,todos',
            'titulo' => 'required|string|max:255',
            'contenido' => 'required|string',
            'fecha_publicacion' => 'required|date',
            'archivo' => 'nullable|file|mimes:pdf,docx',
            'rol_id' => 'nullable|integer',
            'usuarios' => 'nullable|array',
            'usuarios.*' => 'integer',
        ]);

        $user = Auth::user();
        // Validar permiso de envío
        $roleName = optional($user->role)->nombre ?? '';
        $roleLower = mb_strtolower($roleName);
        $allowedKeywords = ['rector','administrador','coordinador','tesorero'];
        $canSend = false; foreach ($allowedKeywords as $k) { if (mb_stripos($roleLower, $k) !== false) { $canSend = true; break; } }
        if (! $canSend) {
            return back()->with('error', 'No está autorizado para publicar circulares');
        }

        // Crear circular primero sin archivo para obtener id y usar secuencia numérica
        \DB::beginTransaction();
        try {
            $circularData = [
                'titulo' => $request->titulo,
                'contenido' => $request->contenido,
                'fecha_publicacion' => $request->fecha_publicacion,
                'archivo' => null,
            ];
            // Incluir creador_id solo si la columna existe (migraciones pendientes pueden no tenerla aún)
            if (\Illuminate\Support\Facades\Schema::hasColumn('circulars', 'creador_id')) {
                $circularData['creador_id'] = $user->id;
            }
            $circular = Circular::create($circularData);

            // manejar archivo si existe: intentar subir primero al disco ftp_matriculas (raíz),
            // y si falla hacer fallback a disco 'public' bajo 'circulares/'.
            if ($request->hasFile('archivo')) {
                $file = $request->file('archivo');
                $ext = $file->getClientOriginalExtension();
                $seq = str_pad($circular->id, 4, '0', STR_PAD_LEFT);
                $filename = "Circular_{$seq}." . $ext;

                try {
                    $disk = \Illuminate\Support\Facades\Storage::disk('ftp_matriculas');
                    // Intentar escribir en la raíz del disco FTP (misma estrategia que comprobantes)
                    $stored = $disk->putFileAs('', $file, $filename);
                    if ($stored === false || $stored === null) {
                        throw new \Exception('ftp putFileAs returned false/null');
                    }
                    // putFileAs suele devolver la ruta relativa; en nuestro caso será el nombre del archivo
                    $circular->archivo = is_string($stored) ? $stored : $filename;
                    $circular->save();
                } catch (\Throwable $e) {
                    logger()->warning('guardarCircular: fallo guardando en ftp_matriculas, fallback a public: ' . $e->getMessage());
                    $path = $file->storeAs('circulares', $filename, 'public');
                    $circular->archivo = $path;
                    $circular->save();
                }
            }

            // Crear notificaciones para destinatarios según modo (marcarlas como solo lectura/tipo=circular)
            $modo = $request->modo;
            $destinatarios = [];

            if ($modo === 'todos') {
                $destinatarios = \App\Models\User::pluck('id')->toArray();
            } elseif ($modo === 'rol') {
                if ($request->filled('usuarios') && is_array($request->usuarios) && count($request->usuarios) > 0) {
                    $destinatarios = array_map('intval', $request->usuarios);
                } else {
                    if (! $request->rol_id) {
                        \DB::rollBack();
                        return back()->withErrors(['rol_id' => 'Seleccione un grupo para enviar'])->withInput();
                    }
                    $destinatarios = \App\Models\User::where('roles_id', $request->rol_id)->pluck('id')->toArray();
                }
            }

            foreach ($destinatarios as $destId) {
                if ($destId == $user->id) continue;
                Notificacion::create([
                    'usuario_id' => $destId,
                    'titulo' => 'Nueva circular: ' . $circular->titulo,
                    'mensaje' => \Illuminate\Support\Str::limit($circular->contenido, 200),
                    'leida' => false,
                    'fecha' => now(),
                    'creador_id' => $user->id,
                    'solo_acudiente_responde' => false,
                    'solo_lectura' => true,
                    'tipo' => 'circular',
                ]);
            }

            \DB::commit();
        } catch (\Throwable $e) {
            \DB::rollBack();
            return back()->with('error', 'Error al crear la circular: ' . $e->getMessage());
        }

        return redirect()->route('comunicacion.circulares')->with('success', 'Circular publicada y notificaciones enviadas correctamente');
    }

    /**
     * Eliminar una circular (sólo Rector o Administrador del sistema).
     * Elimina también el archivo almacenado en el disco `public` si existe.
     */
    public function eliminarCircular(Request $request, $id)
    {
        $user = Auth::user();
        if (! $user) abort(401);

        $roleName = optional($user->role)->nombre ?? '';
        $roleLower = mb_strtolower($roleName);
        $isAllowed = false;
        if (mb_stripos($roleLower, 'rector') !== false || mb_stripos($roleLower, 'administrador') !== false) {
            $isAllowed = true;
        }

        if (! $isAllowed) {
            if ($request->wantsJson() || $request->ajax()) return response()->json(['error' => 'No autorizado'], 403);
            return back()->with('error', 'No está autorizado para eliminar circulares');
        }

        $circ = Circular::findOrFail($id);
        // borrar archivo si existe (usar mismo disco que Matrículas)
        if (! empty($circ->archivo)) {
            try {
                \Illuminate\Support\Facades\Storage::disk('ftp_matriculas')->delete($circ->archivo);
            } catch (\Throwable $e) {
                // no impedir eliminación del registro si el archivo no se puede borrar; loguear opcionalmente
                logger()->warning('eliminarCircular: no se pudo borrar archivo en ftp_matriculas: ' . $e->getMessage());
            }
        }

        $circ->delete();

        if ($request->wantsJson() || $request->ajax()) return response()->json(['message' => 'Circular eliminada correctamente'], 200);
        return redirect()->route('comunicacion.circulares')->with('success', 'Circular eliminada correctamente');
    }
}
