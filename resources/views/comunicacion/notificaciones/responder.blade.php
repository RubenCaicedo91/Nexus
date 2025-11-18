@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="card">
        <div class="card-header bg-warning">
            <strong>Responder notificación</strong>
        </div>
        <div class="card-body">
            <h5>{{ $notif->titulo }}</h5>
            <p class="text-muted">{{ $notif->mensaje }}</p>

            <form method="POST" action="{{ route('comunicacion.notificaciones.responder.enviar', $notif->id) }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Asunto</label>
                    <input type="text" name="asunto" class="form-control" value="Re: {{ $notif->titulo }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Mensaje</label>
                    <textarea name="contenido" class="form-control" rows="5" required></textarea>
                </div>
                <button class="btn btn-primary">Enviar respuesta</button>
                <a href="{{ route('comunicacion.notificaciones') }}" class="btn btn-secondary ms-2">Cancelar</a>
            </form>
        </div>
    </div>
</div>
@endsection
