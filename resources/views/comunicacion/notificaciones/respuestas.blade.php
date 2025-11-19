@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Respuestas al grupo de notificaciones</h5>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <strong>Título:</strong> {{ $groupInfo['titulo'] ?? '-' }}<br>
                <strong>Mensaje:</strong> {{ Str::limit($groupInfo['mensaje'] ?? '', 300) }}<br>
                <strong>Creado:</strong> {{ optional($groupInfo['created_at'])->toDateTimeString() ?? '-' }}
            </div>

            <div class="mb-3">
                <form method="POST" action="{{ route('comunicacion.notificaciones.grupo.eliminar', $groupInfo['group_key']) }}" onsubmit="return confirm('¿Eliminar este grupo de notificaciones para los destinatarios? Esta acción puede ocultar las notificaciones para los usuarios.')">
                    @csrf
                    <button type="submit" class="btn btn-danger">Eliminar grupo (ocultar notificaciones)</button>
                    <a href="{{ route('comunicacion.notificaciones') }}" class="btn btn-secondary ms-2">Volver a notificaciones</a>
                </form>
            </div>

            @if($mensajes->isEmpty())
                <div class="alert alert-info">No se han recibido respuestas asociadas a este grupo.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-striped table-sm">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Remitente</th>
                                <th>Destinatario</th>
                                <th>Asunto</th>
                                <th>Contenido</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($mensajes as $m)
                                <tr>
                                    <td>{{ $m->created_at->toDateTimeString() }}</td>
                                    <td>{{ $m->remitente ? $m->remitente->name : '—' }}</td>
                                    <td>{{ $m->destinatario ? $m->destinatario->name : '—' }}</td>
                                    <td>{{ $m->asunto }}</td>
                                    <td>{{ Str::limit($m->contenido, 200) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
