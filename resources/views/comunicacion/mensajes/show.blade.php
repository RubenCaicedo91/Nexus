@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <strong>Asunto:</strong> {{ $mensaje->asunto }}
            </div>
            <div>
                @if(Auth::id() === $mensaje->remitente_id)
                    <a href="{{ route('comunicacion.mensajes.enviados') }}" class="btn btn-sm btn-outline-secondary">Volver</a>
                @else
                    <a href="{{ route('comunicacion.mensajes') }}" class="btn btn-sm btn-outline-secondary">Volver</a>
                @endif

                @if(Auth::id() === $mensaje->destinatario_id && $mensaje->leido)
                    <form action="{{ route('comunicacion.mensajes.no_leer', $mensaje->id) }}" method="POST" style="display:inline-block;">
                        @csrf
                        <button class="btn btn-sm btn-warning">Marcar como no leído</button>
                    </form>
                @endif

                <a href="{{ route('comunicacion.mensajes.responder.form', $mensaje->id) }}" class="btn btn-sm btn-primary">Responder</a>

                @if(Auth::id() === $mensaje->remitente_id)
                    <form action="{{ route('comunicacion.mensajes.destroy', $mensaje->id) }}" method="POST" style="display:inline-block;">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-danger" onclick="return confirm('Eliminar este mensaje?')">Eliminar</button>
                    </form>
                @endif
            </div>
        </div>
        <div class="card-body">
            <p><strong>Remitente:</strong> {{ optional($mensaje->remitente)->name ?? $mensaje->remitente_id }}</p>
            <p><strong>Destinatario:</strong> {{ optional($mensaje->destinatario)->name ?? $mensaje->destinatario_id }}</p>
            <p><strong>Fecha:</strong> {{ $mensaje->created_at->format('d/m/Y H:i') }}</p>
            <hr>
            <div class="mb-3">{!! nl2br(e($mensaje->contenido)) !!}</div>
        </div>
    </div>
</div>
@endsection
