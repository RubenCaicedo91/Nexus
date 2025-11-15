@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="card shadow-sm rounded overflow-hidden">

        <!-- Encabezado oscuro -->
        <div class="bg-dark text-white p-3 d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-semibold mb-0">
                    <i class="bi bi-clipboard-check me-2 text-info"></i> Seguimientos
                </h2>
                <p class="small mb-0 text-light">Listado de seguimientos realizados a los estudiantes.</p>
            </div>
            <a href="{{ route('orientacion.seguimientos.create') }}" class="btn btn-info">
                <i class="bi bi-plus-circle me-1"></i> Nuevo seguimiento
            </a>
        </div>

        <!-- Separador -->
        <hr class="m-0">

        <!-- Contenido -->
        <div class="p-4 bg-light">
            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <table class="table table-hover table-striped mb-0 align-middle">
                        <thead class="table-secondary">
                            <tr>
                                <th>ID</th>
                                <th>Estudiante</th>
                                <th>Fecha</th>
                                <th>Observaciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($seguimientos as $seg)
                                <tr>
                                    <td>{{ $seg->id }}</td>
                                    <td>{{ $seg->estudiante_id }}</td>
                                    <td>{{ \Carbon\Carbon::parse($seg->fecha)->format('d/m/Y') }}</td>
                                    <td>{{ Str::limit($seg->observaciones, 80) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">
                                        <i class="bi bi-info-circle me-1"></i> No hay seguimientos registrados.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
