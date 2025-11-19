@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="card">
        <div class="card-header">
            <strong>Responder a:</strong> {{ optional($mensaje->remitente)->name ?? $mensaje->remitente_id }}
        </div>
        <div class="card-body">
            <form action="{{ route('comunicacion.mensajes.responder.enviar', $mensaje->id) }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Asunto</label>
                    <input type="text" name="asunto" class="form-control" value="RE: {{ $mensaje->asunto }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Contenido</label>
                    <textarea name="contenido" rows="6" class="form-control" required></textarea>
                </div>
                <button class="btn btn-primary">Enviar respuesta</button>
                <a href="{{ route('comunicacion.mensajes') }}" class="btn btn-secondary">Cancelar</a>
            </form>
        </div>
    </div>
</div>
@endsection
