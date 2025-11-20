@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="bi bi-send-fill me-2"></i> Mensajes Enviados
            </div>
            <div>
                <div class="btn-group" role="group" aria-label="Bandejas">
                    <a href="{{ route('comunicacion.mensajes') }}" class="btn btn-sm @if(request()->routeIs('comunicacion.mensajes')) btn-primary @else btn-outline-secondary @endif">Bandeja de Entrada</a>
                    <a href="{{ route('comunicacion.mensajes.enviados') }}" class="btn btn-sm @if(request()->routeIs('comunicacion.mensajes.enviados')) btn-primary @else btn-outline-secondary @endif">Buzón de Salida</a>
                </div>
                <a href="{{ route('comunicacion.mensajes') }}#" class="btn btn-sm btn-outline-secondary ms-2">Enviar</a>
            </div>
        </div>
        <div class="card-body">
            <table class="table table-hover align-middle">
                <thead class="table-secondary">
                    <tr>
                        <th>Destinatario</th>
                        <th>Asunto</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($mensajes as $msg)
                        <tr>
                            <td>{{ optional($msg->destinatario)->name ?? $msg->destinatario_id }}</td>
                            <td>{{ $msg->asunto }}</td>
                            <td>{{ $msg->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                <a href="{{ route('comunicacion.mensajes.show', $msg->id) }}" class="btn btn-sm btn-outline-primary">Ver</a>
                                <form action="{{ route('comunicacion.mensajes.destroy', $msg->id) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('Eliminar mensaje enviado?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted">No hay mensajes enviados</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
