@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-clipboard-list me-2"></i>Seguimiento de Estudiantes
                    </h4>
                    <div class="d-flex gap-2">
                        <a href="{{ route('seguimientos.dashboard') }}" class="btn btn-light">
                            <i class="fas fa-chart-bar me-2"></i>Dashboard
                        </a>
                        <a href="{{ route('seguimientos.create') }}" class="btn btn-success">
                            <i class="fas fa-plus me-2"></i>Nuevo Seguimiento
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    {{-- Filtros --}}
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <form method="GET" action="{{ route('seguimientos.index') }}" class="row g-3">
                                <div class="col-md-3">
                                    <label for="buscar" class="form-label">Buscar</label>
                                    <input type="text" name="buscar" id="buscar" class="form-control" 
                                           value="{{ request('buscar') }}" placeholder="Título, descripción o estudiante...">
                                </div>

                                <div class="col-md-2">
                                    <label for="tipo_seguimiento" class="form-label">Tipo</label>
                                    <select name="tipo_seguimiento" id="tipo_seguimiento" class="form-select">
                                        <option value="">Todos</option>
                                        @foreach(\App\Models\Seguimiento::TIPOS_SEGUIMIENTO as $key => $value)
                                            <option value="{{ $key }}" {{ request('tipo_seguimiento') == $key ? 'selected' : '' }}>
                                                {{ $value }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-2">
                                    <label for="estado_seguimiento" class="form-label">Estado</label>
                                    <select name="estado_seguimiento" id="estado_seguimiento" class="form-select">
                                        <option value="">Todos</option>
                                        @foreach(\App\Models\Seguimiento::ESTADOS_SEGUIMIENTO as $key => $value)
                                            <option value="{{ $key }}" {{ request('estado_seguimiento') == $key ? 'selected' : '' }}>
                                                {{ $value }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-2">
                                    <label for="nivel_gravedad" class="form-label">Gravedad</label>
                                    <select name="nivel_gravedad" id="nivel_gravedad" class="form-select">
                                        <option value="">Todas</option>
                                        @foreach(\App\Models\Seguimiento::NIVELES_GRAVEDAD as $key => $value)
                                            <option value="{{ $key }}" {{ request('nivel_gravedad') == $key ? 'selected' : '' }}>
                                                {{ $value }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label for="estudiante_id" class="form-label">Estudiante</label>
                                    <select name="estudiante_id" id="estudiante_id" class="form-select">
                                        <option value="">Todos</option>
                                        @foreach($estudiantes as $estudiante)
                                            <option value="{{ $estudiante->id }}" {{ request('estudiante_id') == $estudiante->id ? 'selected' : '' }}>
                                                {{ $estudiante->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                @if(auth()->user()->roles->nombre !== 'Acudiente' && auth()->user()->roles->nombre !== 'Docente')
                                <div class="col-md-3">
                                    <label for="responsable_id" class="form-label">Responsable</label>
                                    <select name="responsable_id" id="responsable_id" class="form-select">
                                        <option value="">Todos</option>
                                        @foreach($responsables as $responsable)
                                            <option value="{{ $responsable->id }}" {{ request('responsable_id') == $responsable->id ? 'selected' : '' }}>
                                                {{ $responsable->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @endif

                                <div class="col-md-2">
                                    <label for="fecha_desde" class="form-label">Desde</label>
                                    <input type="date" name="fecha_desde" id="fecha_desde" class="form-control" value="{{ request('fecha_desde') }}">
                                </div>

                                <div class="col-md-2">
                                    <label for="fecha_hasta" class="form-label">Hasta</label>
                                    <input type="date" name="fecha_hasta" id="fecha_hasta" class="form-control" value="{{ request('fecha_hasta') }}">
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">Filtros Rápidos</label>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <div class="form-check">
                                            <input type="checkbox" name="solo_activos" id="solo_activos" class="form-check-input" 
                                                   value="1" {{ request('solo_activos') ? 'checked' : '' }}>
                                            <label for="solo_activos" class="form-check-label">Solo Activos</label>
                                        </div>
                                        <div class="form-check">
                                            <input type="checkbox" name="gravedad_alta" id="gravedad_alta" class="form-check-input" 
                                                   value="1" {{ request('gravedad_alta') ? 'checked' : '' }}>
                                            <label for="gravedad_alta" class="form-check-label">Gravedad Alta</label>
                                        </div>
                                        <div class="form-check">
                                            <input type="checkbox" name="requiere_revision" id="requiere_revision" class="form-check-input" 
                                                   value="1" {{ request('requiere_revision') ? 'checked' : '' }}>
                                            <label for="requiere_revision" class="form-check-label">Requieren Revisión</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search me-2"></i>Filtrar
                                    </button>
                                    <a href="{{ route('seguimientos.index') }}" class="btn btn-secondary">
                                        <i class="fas fa-times me-2"></i>Limpiar
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    {{-- Estadísticas rápidas --}}
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <i class="fas fa-clipboard-list fa-2x mb-2"></i>
                                    <h5>{{ $estadisticas['total'] ?? 0 }}</h5>
                                    <small>Total Seguimientos</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <i class="fas fa-play-circle fa-2x mb-2"></i>
                                    <h5>{{ $estadisticas['activos'] ?? 0 }}</h5>
                                    <small>Activos</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-danger text-white">
                                <div class="card-body text-center">
                                    <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                                    <h5>{{ $estadisticas['gravedad_alta'] ?? 0 }}</h5>
                                    <small>Gravedad Alta</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body text-center">
                                    <i class="fas fa-clock fa-2x mb-2"></i>
                                    <h5>{{ $estadisticas['requieren_revision'] ?? 0 }}</h5>
                                    <small>Requieren Revisión</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Tabla de seguimientos --}}
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Estudiante</th>
                                    <th>Título / Tipo</th>
                                    <th>Responsable</th>
                                    <th>Estado</th>
                                    <th>Gravedad</th>
                                    <th>Fecha Identificación</th>
                                    <th>Próxima Revisión</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($seguimientos as $seguimiento)
                                <tr class="{{ $seguimiento->requiereRevision() ? 'table-warning' : '' }}">
                                    <td>
                                        <strong>#{{ $seguimiento->id }}</strong>
                                        @if($seguimiento->confidencial)
                                            <i class="fas fa-lock text-warning ms-1" title="Confidencial"></i>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-user-graduate me-2 text-primary"></i>
                                            <div>
                                                <strong>{{ $seguimiento->estudiante->name }}</strong><br>
                                                <small class="text-muted">{{ $seguimiento->estudiante->email }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <strong>{{ $seguimiento->titulo }}</strong>
                                            <br>
                                            <span class="badge bg-secondary">{{ $seguimiento->tipo_seguimiento_formateado }}</span>
                                            @if($seguimiento->area_enfoque)
                                                <br><small class="text-muted">{{ $seguimiento->area_enfoque_formateada }}</small>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        @if($seguimiento->responsable)
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-user-tie me-2 text-success"></i>
                                                <div>
                                                    <strong>{{ $seguimiento->responsable->name }}</strong><br>
                                                    <small class="text-muted">{{ $seguimiento->responsable->email }}</small>
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-muted">Sin asignar</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $badgeClass = match($seguimiento->estado_seguimiento) {
                                                'activo' => 'bg-success',
                                                'en_proceso' => 'bg-info',
                                                'pausado' => 'bg-warning',
                                                'completado' => 'bg-secondary',
                                                'derivado' => 'bg-primary',
                                                default => 'bg-secondary'
                                            };
                                        @endphp
                                        <span class="badge {{ $badgeClass }}">
                                            {{ $seguimiento->estado_seguimiento_formateado }}
                                        </span>
                                        @if($seguimiento->numero_sesiones > 0)
                                            <br><small class="text-muted">{{ $seguimiento->numero_sesiones }} sesión(es)</small>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $gravedadClass = match($seguimiento->nivel_gravedad) {
                                                'bajo' => 'text-success',
                                                'medio' => 'text-warning',
                                                'alto' => 'text-danger',
                                                'critico' => 'text-danger fw-bold',
                                                default => 'text-secondary'
                                            };
                                        @endphp
                                        <span class="{{ $gravedadClass }}">
                                            <i class="fas fa-exclamation-triangle me-1"></i>
                                            {{ $seguimiento->nivel_gravedad_formateado }}
                                        </span>
                                    </td>
                                    <td>
                                        <i class="fas fa-calendar me-1"></i>
                                        {{ ($seguimiento->fecha_identificacion ?? $seguimiento->fecha)->format('d/m/Y') }}
                                        <br>
                                        <small class="text-muted">
                                            Hace {{ $seguimiento->dias_desde_identificacion }} días
                                        </small>
                                    </td>
                                    <td>
                                        @if($seguimiento->fecha_proxima_revision)
                                            <div class="{{ $seguimiento->requiereRevision() ? 'text-danger fw-bold' : 'text-info' }}">
                                                <i class="fas fa-calendar-alt me-1"></i>
                                                {{ $seguimiento->fecha_proxima_revision->format('d/m/Y') }}
                                                @if($seguimiento->requiereRevision())
                                                    <br><small class="text-danger">¡Vencida!</small>
                                                @elseif($seguimiento->dias_para_revision !== null && $seguimiento->dias_para_revision <= 7)
                                                    <br><small class="text-warning">En {{ $seguimiento->dias_para_revision }} días</small>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-muted">No programada</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('seguimientos.show', $seguimiento) }}" class="btn btn-sm btn-outline-primary" title="Ver detalles">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            
                                            @if($seguimiento->puedeSerEditado() && 
                                                (auth()->user()->id === $seguimiento->responsable_id || 
                                                 in_array(auth()->user()->roles->nombre, ['Rector', 'orientador'])))
                                                <a href="{{ route('seguimientos.edit', $seguimiento) }}" class="btn btn-sm btn-outline-warning" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            @endif

                                            @if($seguimiento->esActivo() && 
                                                (auth()->user()->id === $seguimiento->responsable_id || 
                                                 in_array(auth()->user()->roles->nombre, ['Rector', 'orientador'])))
                                                <button type="button" class="btn btn-sm btn-outline-success btn-registrar-sesion" 
                                                        data-seguimiento-id="{{ $seguimiento->id }}" title="Registrar sesión">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            @endif

                                            @if(auth()->user()->id === $seguimiento->responsable_id || 
                                                auth()->user()->roles->nombre === 'Rector')
                                                <button type="button" class="btn btn-sm btn-outline-danger btn-eliminar-seguimiento" 
                                                        data-seguimiento-id="{{ $seguimiento->id }}" title="Eliminar">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center py-5">
                                        <div class="d-flex flex-column align-items-center">
                                            <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                                            <h5 class="text-muted">No hay seguimientos registrados</h5>
                                            <p class="text-muted">Comience creando un nuevo seguimiento</p>
                                            <a href="{{ route('seguimientos.create') }}" class="btn btn-primary">
                                                <i class="fas fa-plus me-2"></i>Crear Primer Seguimiento
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Paginación --}}
                    @if($seguimientos->hasPages())
                        <div class="d-flex justify-content-center mt-4">
                            {{ $seguimientos->appends(request()->query())->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal para registrar sesión --}}
<div class="modal fade" id="registrarSesionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Registrar Nueva Sesión</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="registrarSesionForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="observaciones_sesion" class="form-label">Observaciones de la Sesión <span class="text-danger">*</span></label>
                        <textarea name="observaciones_sesion" id="observaciones_sesion" class="form-control" rows="4" 
                                  placeholder="Describa lo observado en esta sesión..." required></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="acciones_realizadas" class="form-label">Acciones Realizadas</label>
                        <textarea name="acciones_realizadas" id="acciones_realizadas" class="form-control" rows="3" 
                                  placeholder="Describa las acciones tomadas..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="fecha_proxima_revision" class="form-label">Próxima Revisión</label>
                        <input type="date" name="fecha_proxima_revision" id="fecha_proxima_revision" class="form-control" 
                               min="{{ date('Y-m-d', strtotime('+1 day')) }}">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-success">Registrar Sesión</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal para eliminar seguimiento --}}
<div class="modal fade" id="eliminarSeguimientoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Eliminar Seguimiento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro de que desea eliminar este seguimiento?</p>
                <p class="text-danger"><strong>Esta acción no se puede deshacer.</strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form id="eliminarSeguimientoForm" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Eliminar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Manejar botones de registrar sesión
    document.querySelectorAll('.btn-registrar-sesion').forEach(function(button) {
        button.addEventListener('click', function() {
            const seguimientoId = this.getAttribute('data-seguimiento-id');
            const modal = new bootstrap.Modal(document.getElementById('registrarSesionModal'));
            const form = document.getElementById('registrarSesionForm');
            form.action = `/seguimientos/${seguimientoId}/sesion`;
            modal.show();
        });
    });

    // Manejar botones de eliminar seguimiento
    document.querySelectorAll('.btn-eliminar-seguimiento').forEach(function(button) {
        button.addEventListener('click', function() {
            const seguimientoId = this.getAttribute('data-seguimiento-id');
            const modal = new bootstrap.Modal(document.getElementById('eliminarSeguimientoModal'));
            const form = document.getElementById('eliminarSeguimientoForm');
            form.action = `/seguimientos/${seguimientoId}`;
            modal.show();
        });
    });
});

// Auto-refresh cada 10 minutos para mantener actualizado
setInterval(function() {
    if (!document.hidden) {
        location.reload();
    }
}, 600000); // 10 minutos
</script>
@endsection