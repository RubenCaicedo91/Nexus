@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="card shadow-sm rounded overflow-hidden">

        <!-- Encabezado oscuro -->
        <div class="bg-dark text-white p-3">
            <h2 class="fw-semibold mb-0">
                <i class="bi bi-file-earmark-text me-2 text-info"></i> Circulares Institucionales
            </h2>
            <p class="small mb-0 text-light">Listado actualizado de circulares emitidas por la institución.</p>
        </div>

        <!-- Separador -->
        <hr class="m-0">

        <!-- Contenido -->
        <div class="p-4 bg-light">
            @if($circulares->isEmpty())
                <div class="alert alert-info text-center">
                    No hay circulares registradas en el sistema.
                </div>
            @else
                <table class="table table-hover table-striped align-middle">
                    <thead class="table-secondary">
                        <tr>
                            <th>Título</th>
                            <th>Contenido</th>
                            <th>Fecha</th>
                            <th>Archivo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($circulares as $circ)
                            <tr>
                                <td>{{ $circ->titulo }}</td>
                                <td>{{ Str::limit($circ->contenido, 80) }}</td>
                                <td>{{ \Carbon\Carbon::parse($circ->fecha_publicacion)->format('d/m/Y') }}</td>
                                <td>
                                    @if($circ->archivo)
                                        <a href="{{ asset('storage/'.$circ->archivo) }}" target="_blank" class="btn btn-sm btn-outline-info">
                                            <i class="bi bi-download me-1"></i> Descargar
                                        </a>
                                    @else
                                        <span class="text-muted">Sin archivo</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>
@endsection
