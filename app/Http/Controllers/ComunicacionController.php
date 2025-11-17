<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Mensaje;
use App\Models\Notificacion;
use App\Models\Circular;

class ComunicacionController extends Controller
{
    // Vista principal del módulo
    public function index()
    {
        return view('comunicacion.index');
    }

    // ---------------- Mensajes ----------------
    public function listarMensajes()
    {
        // Bandeja de entrada del usuario autenticado
        // Eager load remitente para mostrar nombre en la vista
        $mensajes = Mensaje::where('destinatario_id', Auth::id())->with('remitente')->latest()->get();
        // Mensajes enviados por el usuario (para mostrar en el mismo panel)
        $mensajesEnviados = Mensaje::where('remitente_id', Auth::id())->with('destinatario')->latest()->get();
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
        $mensajes = Mensaje::where('remitente_id', Auth::id())->with('destinatario')->latest()->get();
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
            $thread = Mensaje::where(function($q) use ($rootId) {
                $q->where('id', $rootId)->orWhere('parent_id', $rootId);
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
        // Permitir eliminar si es remitente o destinatario (borrado físico)
        if ($mensaje->remitente_id !== $userId && $mensaje->destinatario_id !== $userId) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['error' => 'No autorizado'], 403);
            }
            return back()->with('error', 'No autorizado');
        }

        $mensaje->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['message' => 'Mensaje eliminado'], 200);
        }

        return redirect()->route('comunicacion.mensajes.enviados')->with('success', 'Mensaje eliminado');
    }

    // ---------------- Notificaciones ----------------
    public function listarNotificaciones()
    {
        $notificaciones = Notificacion::where('usuario_id', Auth::id())->latest()->get();
        // roles para formulario de envío
        $roles = \App\Models\RolesModel::orderBy('nombre')->get();
        return view('comunicacion.notificaciones.index', compact('notificaciones', 'roles'));
    }

    public function marcarNotificacionLeida($id)
    {
        $notif = Notificacion::findOrFail($id);
        $notif->leida = true;
        $notif->save();

        return back()->with('success', 'Notificación marcada como leída');
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
        ]);

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
        } else {
            return back()->withErrors(['modo' => 'Modo de envío inválido'])->withInput();
        }

        foreach ($destinatarios as $destId) {
            // Evitar enviar notificación a self
            if ($destId == Auth::id()) continue;

            Notificacion::create([
                'usuario_id' => $destId,
                'titulo' => $request->titulo,
                'mensaje' => $request->mensaje,
                'leida' => false,
                'fecha' => now(),
            ]);
        }

        return redirect()->route('comunicacion.notificaciones')->with('success', 'Notificación(es) enviada(s) correctamente');
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
            ]);
        }

        return redirect()->route('comunicacion.circulares')->with('success', 'Circular publicada y notificaciones enviadas correctamente');
    }
}
