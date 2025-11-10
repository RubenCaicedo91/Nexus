@extends('layouts.app')

@section('content')
<div class="container">
    <h2 class="mb-4">✉️ Mensajes Internos</h2>

    <!-- Formulario de envío -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">Enviar Mensaje</div>
        <div class="card-body">
            <form action="{{ route('comunicacion.mensajes.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="destinatario_id" class="form-label">Destinatario</label>
                    <input type="text" name="destinatario_id" id="destinatario_id" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="asunto" class="form-label">Asunto</label>
                    <input type="text" name="asunto" id="asunto" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="contenido" class="form-label">Contenido</label>
                    <textarea name="contenido" id="contenido" rows="4" class="form-control" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Enviar ➡️</button>
            </form>
        </div>
    </div>

    <!-- Bandeja de entrada -->
    <div class="card">
        <div class="card-header bg-secondary text-white">Bandeja de Entrada</div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
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
                            <td>{{ $msg->leido ? 'Leído' : 'No leído' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center">No hay mensajes</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection