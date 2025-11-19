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
            <div class="d-flex gap-2">
                <a href="{{ route('orientacion.informes.export_pdf', request()->query()) }}" class="btn btn-sm btn-outline-light">Exportar PDF</a>
                <a href="{{ route('orientacion.informes.export_excel', request()->query()) }}" class="btn btn-sm btn-outline-light">Exportar Excel</a>
            </div>
        </div>

        <!-- Separador -->
        <hr class="m-0">

        <!-- Contenido: filtros y resultados -->
        <div class="p-4 bg-light">
            <div class="mb-3">
                <form action="{{ route('orientacion.informes') }}" method="GET" class="row g-2">
                    <div class="col-md-4">
                        <select name="solicitante_id" class="form-select">
                            <option value="">-- Solicitante (Todos) --</option>
                            @foreach($solicitantes as $s)
                                <option value="{{ $s->id }}" {{ request('solicitante_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="rol" class="form-select">
                            <option value="">-- Rol (todos) --</option>
                            @foreach($roles as $r)
                                <option value="{{ $r }}" {{ request('rol') == $r ? 'selected' : '' }}>{{ $r }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="tipo" class="form-select">
                            <option value="">-- Tipo de cita (todos) --</option>
                            @foreach($tipos as $key => $label)
                                <option value="{{ $key }}" {{ request('tipo') == $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 d-grid">
                        <button class="btn btn-primary">Filtrar</button>
                    </div>
                </form>
                    {{-- Resumen de filtros aplicados --}}
                    <div class="mt-3">
                        @php
                            $fSolicitante = null;
                            if(request('solicitante_id') && isset($solicitantes)){
                                $fSolicitante = $solicitantes->firstWhere('id', request('solicitante_id'));
                            }
                            $fRol = request('rol');
                            $fTipo = request('tipo');
                        @endphp

                        <div class="small text-muted">Filtros aplicados:</div>
                        <div class="d-flex gap-2 flex-wrap mt-1 align-items-center">
                            <span class="badge bg-secondary">Solicitante: {{ $fSolicitante ? $fSolicitante->name : 'Todos' }}</span>
                            <span class="badge bg-secondary">Rol: {{ $fRol ?: 'Todos' }}</span>
                            <span class="badge bg-secondary">Tipo: {{ $fTipo ? ($tipos[$fTipo] ?? $fTipo) : 'Todos' }}</span>
                            
                        </div>
                            {{-- Comparación entre tipos definidos y tipos presentes en la tabla --}}
                            {{-- Banner de tipos no definidos ocultado por solicitud del equipo. --}}

                            {{-- El banner de "Tipos definidos sin registros" fue retirado por solicitud. --}}
                    </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <table class="table table-hover table-striped mb-0 align-middle">
                        <thead class="table-secondary">
                            <tr>
                                <th>ID</th>
                                <th>Solicitante</th>
                                <th>Rol</th>
                                <th>Tipo</th>
                                <th>Fecha solicitud</th>
                                <th>Atendido por</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($citas as $cita)
                                <tr>
                                    <td>{{ $cita->id }}</td>
                                    <td>{{ optional($cita->solicitante)->name ?? 'N/A' }}</td>
                                    <td>{{ optional(optional($cita->solicitante)->role)->nombre ?? 'N/A' }}</td>
                                    <td>{{ $tipos[$cita->tipo_cita] ?? $cita->tipo_cita }}</td>
                                    <td>{{ $cita->fecha_solicitada ? \Carbon\Carbon::parse($cita->fecha_solicitada)->format('d/m/Y') . ' ' . ($cita->hora_solicitada ?? '') : 'Sin fecha' }}</td>
                                    <td>{{ optional($cita->orientador)->name ?? 'Sin asignar' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        <i class="bi bi-info-circle me-1"></i> No se encontraron citas completadas con esos filtros.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-3">{{ $citas->links() }}</div>
        </div>
    </div>
</div>
@endsection
