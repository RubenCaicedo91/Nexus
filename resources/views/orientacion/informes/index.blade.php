@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="card shadow-sm rounded overflow-hidden">

        <!-- Encabezado oscuro -->
        <div class="bg-dark text-white p-3 d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-semibold mb-0">
                    <i class="bi bi-journal-medical me-2 text-success"></i> Informes Psicosociales
                </h2>
                <p class="small mb-0 text-light">Listado de informes generados en las citas de orientación.</p>
            </div>
            <a href="{{ route('orientacion.informes.create') }}" class="btn btn-success">
                <i class="bi bi-plus-circle me-1"></i> Nuevo informe
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
                                <th>Cita</th>
                                <th>Fecha cita</th>
                                <th>Descripción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($informes as $informe)
                                <tr>
                                    <td>{{ $informe->id }}</td>
                                    <td>#{{ $informe->cita_id }}</td>
                                    <td>
                                        {{ optional($informe->cita)->fecha 
                                            ? \Carbon\Carbon::parse($informe->cita->fecha)->format('d/m/Y H:i') 
                                            : 'Sin fecha' }}
                                    </td>
                                    <td>{{ Str::limit($informe->descripcion, 80) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">
                                        <i class="bi bi-info-circle me-1"></i> No hay informes registrados.
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
