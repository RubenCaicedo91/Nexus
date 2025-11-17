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
     * Mostrar las respuestas (mensajes) asociadas a un grupo de notificaciones (group_key).
     * Solo puede acceder el creador del grupo o usuarios con roles administrativos.
     */
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
            if (! $notif->solo_acudiente_responde) {
                $canReply = true;
            } else {
                $roleName = optional($user->role)->nombre ?? '';
                if (stripos($roleName, 'acudiente') !== false) $canReply = true;
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

        // Si la notificación está marcada como "solo acudiente puede responder", validar rol
        if ($notif->solo_acudiente_responde) {
            $roleName = optional($user->role)->nombre ?? '';
            if (stripos($roleName, 'acudiente') === false) {
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

        if ($notif->solo_acudiente_responde) {
            $roleName = optional($user->role)->nombre ?? '';
            if (stripos($roleName, 'acudiente') === false) {
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
        return view('comunicacion.circulares.index', compact('circulares', 'roles'));
    }

    public function crearCircular()
    {
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

        $archivo = null;
        if ($request->hasFile('archivo')) {
            $archivo = $request->file('archivo')->store('circulares', 'public');
        }

        $circular = Circular::create([
            'titulo' => $request->titulo,
            'contenido' => $request->contenido,
            'fecha_publicacion' => $request->fecha_publicacion,
            'archivo' => $archivo,
        ]);

        // Crear notificaciones para destinatarios según modo
        $modo = $request->modo;
        $destinatarios = [];

        if ($modo === 'todos') {
            $destinatarios = \App\Models\User::pluck('id')->toArray();
        } elseif ($modo === 'rol') {
            if ($request->filled('usuarios') && is_array($request->usuarios) && count($request->usuarios) > 0) {
                $destinatarios = array_map('intval', $request->usuarios);
            } else {
                if (! $request->rol_id) {
                    return back()->withErrors(['rol_id' => 'Seleccione un grupo para enviar'])->withInput();
                }
                $destinatarios = \App\Models\User::where('roles_id', $request->rol_id)->pluck('id')->toArray();
            }
        }

        foreach ($destinatarios as $destId) {
            if ($destId == Auth::id()) continue;
            // Crear notificación informando sobre la circular (enlace podría añadirse más adelante)
            Notificacion::create([
                'usuario_id' => $destId,
                'titulo' => 'Nueva circular: ' . $circular->titulo,
                'mensaje' => \Illuminate\Support\Str::limit($circular->contenido, 200),
                'leida' => false,
                'fecha' => now(),
                'creador_id' => Auth::id(),
                'solo_acudiente_responde' => false,
            ]);
        }

        return redirect()->route('comunicacion.circulares')->with('success', 'Circular publicada y notificaciones enviadas correctamente');
    }
}
