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
        $mensajes = Mensaje::where('destinatario_id', Auth::id())->latest()->get();
        // Cargar roles para el formulario de envío (grupos)
        $roles = \App\Models\RolesModel::orderBy('nombre')->get();

        return view('comunicacion.mensajes.index', compact('mensajes', 'roles'));
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

    // ---------------- Notificaciones ----------------
    public function listarNotificaciones()
    {
        $notificaciones = Notificacion::where('usuario_id', Auth::id())->latest()->get();
        return view('comunicacion.notificaciones.index', compact('notificaciones'));
    }

    public function marcarNotificacionLeida($id)
    {
        $notif = Notificacion::findOrFail($id);
        $notif->leida = true;
        $notif->save();

        return back()->with('success', 'Notificación marcada como leída');
    }

    // ---------------- Circulares ----------------
    public function listarCirculares()
    {
        $circulares = Circular::latest()->get();
        return view('comunicacion.circulares.index', compact('circulares'));
    }

    public function crearCircular()
    {
        return view('comunicacion.circulares.create');
    }

    public function guardarCircular(Request $request)
    {
        $request->validate([
            'titulo' => 'required|string|max:255',
            'contenido' => 'required|string',
            'fecha_publicacion' => 'required|date',
            'archivo' => 'nullable|file|mimes:pdf,docx',
        ]);

        $archivo = null;
        if ($request->hasFile('archivo')) {
            $archivo = $request->file('archivo')->store('circulares', 'public');
        }

        Circular::create([
            'titulo' => $request->titulo,
            'contenido' => $request->contenido,
            'fecha_publicacion' => $request->fecha_publicacion,
            'archivo' => $archivo,
        ]);

        return redirect()->route('comunicacion.circulares')->with('success', 'Circular publicada correctamente');
    }
}
