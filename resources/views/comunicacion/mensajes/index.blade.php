@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="card shadow-sm rounded overflow-hidden">

        <!-- Encabezado oscuro -->
        <div class="bg-dark text-white p-3">
            <h2 class="fw-semibold mb-0">
                <i class="bi bi-envelope-fill me-2 text-info"></i> Mensajes Internos
            </h2>
            <p class="small mb-0 text-light">Envía y consulta mensajes dentro de la institución.</p>
        </div>

        <!-- Separador -->
        <hr class="m-0">

        <!-- Contenido -->
        <div class="p-4 bg-light">
            <!-- Formulario de envío -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <i class="bi bi-send-check-fill me-1"></i> Enviar Mensaje
                </div>
                <div class="card-body">
                    <form action="{{ route('comunicacion.mensajes.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="destinatario_id" class="form-label fw-bold">Destinatario</label>
                            <input type="text" name="destinatario_id" id="destinatario_id" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="asunto" class="form-label fw-bold">Asunto</label>
                            <input type="text" name="asunto" id="asunto" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="contenido" class="form-label fw-bold">Contenido</label>
                            <textarea name="contenido" id="contenido" rows="4" class="form-control" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send-fill me-1"></i> Enviar
                        </button>
                    </form>
                </div>
            </div>

            <!-- Bandeja de entrada -->
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <i class="bi bi-inbox-fill me-1"></i> Bandeja de Entrada
                </div>
                <div class="card-body">
                    <table class="table table-hover table-striped align-middle">
                        <thead class="table-secondary">
                            <tr>
                                <th>Remitente</th>
                                <th>Asunto</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($mensajes as $msg)
                                <tr>
                                    <td>{{ $msg->remitente_id }}</td>
                                    <td>{{ $msg->asunto }}</td>
                                    <td>{{ $msg->created_at->format('d/m/Y H:i') }}</td>
                                    <td>
                                        @if($msg->leido)
                                            <span class="badge bg-success">Leído</span>
                                        @else
                                            <span class="badge bg-warning text-dark">No leído</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted">No hay mensajes</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
