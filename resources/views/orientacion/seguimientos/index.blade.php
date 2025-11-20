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
            {{-- Enlace de creación removido: creación de seguimientos manejada desde el módulo principal de Seguimientos. --}}
        </div>

        <!-- Separador -->
        <hr class="m-0">

        <!-- Contenido -->
        <div class="p-4 bg-light">
            <div class="mb-3">
                <form method="GET" action="{{ route('orientacion.seguimientos') }}" class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label small">Estudiante / Usuario</label>
                        <select name="usuario_id" class="form-select">
                            <option value="">-- Todos --</option>
                            @foreach(($usuarios ?? collect()) as $u)
                                <option value="{{ $u->id }}" {{ optional($request)->usuario_id == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Tipo seguimiento</label>
                        <select name="tipo" class="form-select">
                            <option value="">-- Todos --</option>
                            @foreach(($tipos ?? []) as $k => $v)
                                <option value="{{ $k }}" {{ optional($request)->tipo == $k ? 'selected' : '' }}>{{ $v }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Estado</label>
                        <select name="estado" class="form-select">
                            <option value="">-- Todos --</option>
                            @foreach(($estados ?? []) as $k => $v)
                                <option value="{{ $k }}" {{ optional($request)->estado == $k ? 'selected' : '' }}>{{ $v }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 text-end">
                        <button class="btn btn-primary w-100"><i class="bi bi-funnel-fill me-1"></i> Filtrar</button>
                    </div>
                </form>
            </div>

            @php
                $canSeeSeguimientos = Auth::check() && method_exists(Auth::user(), 'hasAnyPermission') && Auth::user()->hasAnyPermission(['registrar_sesiones_orientacion','registrar_sesiones_orientacion']);
            @endphp
            <div class="card shadow-sm">
                <div class="card-body p-0">
                    @if(! $canSeeSeguimientos)
                        <div class="p-4 text-center text-warning">Acceso restringido: no tienes permisos para ver o registrar seguimientos en este módulo.</div>
                    @elseif(isset($seguimientosGrouped) && $seguimientosGrouped->count())
                        <table class="table table-hover table-striped mb-0 align-middle">
                            <thead class="table-secondary">
                                <tr>
                                    <th>Estudiante</th>
                                    <th>Seguimientos</th>
                                    <th>Última fecha</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($seguimientosGrouped as $studentId => $items)
                                    @php
                                        $student = optional($items->first()->estudiante);
                                        $count = $items->count();
                                        $last = $items->sortByDesc('fecha')->first();
                                    @endphp
                                    <tr class="table-primary">
                                        <td>
                                            <strong>{{ $student->name ?? 'Sin estudiante (ID: ' . ($studentId == 'sin_estudiante' ? 'N/A' : $studentId) . ')' }}</strong>
                                            <div class="small text-muted">ID: {{ $student->id ?? $studentId }}</div>
                                        </td>
                                        <td>{{ $count }}</td>
                                        <td>{{ $last && $last->fecha ? \Carbon\Carbon::parse($last->fecha)->format('d/m/Y') : '-' }}</td>
                                        <td>
                                            {{-- Botón "Ver" eliminado según solicitud; se mantiene la columna vacía para mantener diseño. --}}
                                        </td>
                                    </tr>

                                    {{-- Filas detalle por seguimiento del estudiante --}}
                                    @foreach($items as $seg)
                                        <tr>
                                            <td class="ps-4">&mdash; {{ $seg->titulo ?? ('Seguimiento #' . $seg->id) }}</td>
                                            <td>{{ $seg->tipo_seguimiento ? ($tipos[$seg->tipo_seguimiento] ?? $seg->tipo_seguimiento) : '-' }}</td>
                                            <td>{{ $seg->fecha ? \Carbon\Carbon::parse($seg->fecha)->format('d/m/Y') : '-' }}</td>
                                            <td>
                                                @if(!empty($seg->is_cita))
                                                    <a href="{{ route('orientacion.citas') }}?cita_id={{ $seg->id }}" class="btn btn-sm btn-outline-secondary">Ver cita</a>
                                                @else
                                                    <a href="{{ route('seguimientos.show', $seg) }}" class="btn btn-sm btn-outline-secondary">Detalles</a>
                                                @endif
                                            </td>
                                        </tr>

                                        {{-- Mostrar lo que escribió el orientador / responsable debajo del seguimiento --}}
                                        @php
                                            if(!empty($seg->is_cita)) {
                                                $authorName = optional($seg->orientador)->name ?? 'Sin orientador';
                                                $content = $seg->resumen_cita ?? $seg->descripcion ?? $seg->motivo ?? '';
                                            } else {
                                                $authorName = optional($seg->responsable)->name ?? 'Sin responsable';
                                                $content = $seg->observaciones ?? $seg->acciones_realizadas ?? $seg->recomendaciones ?? '';
                                            }
                                        @endphp
                                        <tr>
                                            <td colspan="4" class="ps-4">
                                                <div class="small text-muted"><strong>{{ !empty($seg->is_cita) ? 'Orientador:' : 'Responsable:' }}</strong> {{ $authorName }}</div>
                                                <div class="mt-1">{{ \Illuminate\Support\Str::limit(strip_tags($content), 400) }}</div>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="p-4 text-center text-muted">
                            <i class="bi bi-info-circle me-1"></i> No hay seguimientos registrados.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
