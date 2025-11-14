@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="card shadow-sm rounded overflow-hidden">

        <!-- Encabezado oscuro -->
        <div class="bg-dark text-white p-3 d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold mb-0">
                <i class="bi bi-journal-text me-2 text-warning"></i> Listado de Notas
            </h2>
            <a href="{{ route('notas.create') }}" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-circle me-1"></i> Nueva
            </a>
        </div>

        <!-- Separador -->
        <hr class="m-0">

        <!-- Contenido -->
        <div class="p-4 bg-light">
            <!-- Filtros -->
            <form method="GET" class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label fw-bold">Curso</label>
                    <select name="curso_id" class="form-select">
                        <option value="">-- Seleccionar --</option>
                        @foreach($cursos as $c)
                            <option value="{{ $c->id }}" {{ request('curso_id') == $c->id ? 'selected' : '' }}>{{ $c->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Materia</label>
                    <select name="materia_id" class="form-select">
                        <option value="">-- Seleccionar --</option>
                        @foreach($materias as $m)
                            <option value="{{ $m->id }}" {{ request('materia_id') == $m->id ? 'selected' : '' }}>{{ $m->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Periodo</label>
                    <input type="text" name="periodo" class="form-control" placeholder="Ej: 2025-10" value="{{ request('periodo') }}">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-secondary w-100">
                        <i class="bi bi-funnel-fill me-1"></i> Filtrar
                    </button>
                </div>
            </form>

            <!-- Tabla -->
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle">
                    <thead class="table-secondary">
                        <tr>
                            <th>Estudiante</th>
                            <th>Materia</th>
                            <th>Periodo</th>
                            <th>Valor</th>
                            <th>Aprobada</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($notas as $nota)
                            <tr>
                                <td>{{ $nota->matricula->user->name ?? 'N/A' }}</td>
                                <td>{{ $nota->materia->nombre ?? 'N/A' }}</td>
                                <td>{{ $nota->periodo }}</td>
                                <td>{{ number_format($nota->valor, 2) }}</td>
                                <td>
                                    @if($nota->aprobada)
                                        <span class="badge bg-success">Sí</span>
                                    @else
                                        <span class="badge bg-warning text-dark">No</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('notas.edit', $nota) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil-square me-1"></i> Editar
                                    </a>
                                    @if(! $nota->aprobada && optional(Auth::user())->roles_id == 1)
                                        <form action="{{ route('notas.approve', $nota) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Aprobar nota?')">
                                            @csrf
                                            <button class="btn btn-sm btn-success">
                                                <i class="bi bi-check-circle me-1"></i> Aprobar
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <div class="mt-3">
                {{ $notas->withQueryString()->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
