@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-calendar-alt me-2"></i>Gestión de Citas
                    </h4>
                    <div class="d-flex gap-2">
                        <a href="{{ route('citas.calendario') }}" class="btn btn-light">
                            <i class="fas fa-calendar me-2"></i>Ver Calendario
                        </a>
                        <a href="{{ route('citas.create') }}" class="btn btn-success">
                            <i class="fas fa-plus me-2"></i>Nueva Cita
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    {{-- Filtros --}}
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <form method="GET" action="{{ route('citas.index') }}" class="row g-3">
                                <div class="col-md-2">
                                    <label for="estado" class="form-label">Estado</label>
                                    <select name="estado" id="estado" class="form-select">
                                        <option value="">Todos</option>
                                        @foreach(\App\Models\Cita::ESTADOS as $key => $value)
                                            <option value="{{ $key }}" {{ request('estado') == $key ? 'selected' : '' }}>
                                                {{ $value }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-2">
                                    <label for="tipo_cita" class="form-label">Tipo</label>
                                    <select name="tipo_cita" id="tipo_cita" class="form-select">
                                        <option value="">Todos</option>
                                        @foreach(\App\Models\Cita::TIPOS_CITA as $key => $value)
                                            <option value="{{ $key }}" {{ request('tipo_cita') == $key ? 'selected' : '' }}>
                                                {{ $value }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-2">
                                    <label for="prioridad" class="form-label">Prioridad</label>
                                    <select name="prioridad" id="prioridad" class="form-select">
                                        <option value="">Todas</option>
                                        @foreach(\App\Models\Cita::PRIORIDADES as $key => $value)
                                            <option value="{{ $key }}" {{ request('prioridad') == $key ? 'selected' : '' }}>
                                                {{ $value }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                @if(auth()->user()->roles->nombre !== 'Acudiente')
                                <div class="col-md-2">
                                    <label for="orientador_id" class="form-label">Orientador</label>
                                    <select name="orientador_id" id="orientador_id" class="form-select">
                                        <option value="">Todos</option>
                                        @foreach($orientadores as $orientador)
                                            <option value="{{ $orientador->id }}" {{ request('orientador_id') == $orientador->id ? 'selected' : '' }}>
                                                {{ $orientador->name }}
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

                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search me-2"></i>Filtrar
                                    </button>
                                    <a href="{{ route('citas.index') }}" class="btn btn-secondary">
                                        <i class="fas fa-times me-2"></i>Limpiar
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    {{-- Estadísticas rápidas --}}
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body text-center">
                                    <i class="fas fa-clock fa-2x mb-2"></i>
                                    <h5>{{ $citas->where('estado', 'solicitada')->count() }}</h5>
                                    <small>Solicitadas</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <i class="fas fa-calendar-check fa-2x mb-2"></i>
                                    <h5>{{ $citas->where('estado', 'programada')->count() }}</h5>
                                    <small>Programadas</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <i class="fas fa-check-circle fa-2x mb-2"></i>
                                    <h5>{{ $citas->where('estado', 'completada')->count() }}</h5>
                                    <small>Completadas</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-danger text-white">
                                <div class="card-body text-center">
                                    <i class="fas fa-times-circle fa-2x mb-2"></i>
                                    <h5>{{ $citas->where('estado', 'cancelada')->count() }}</h5>
                                    <small>Canceladas</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Tabla de citas --}}
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Solicitante</th>
                                    <th>Estudiante</th>
                                    <th>Tipo</th>
                                    <th>Fecha/Hora</th>
                                    <th>Orientador</th>
                                    <th>Estado</th>
                                    <th>Prioridad</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($citas as $cita)
                                <tr>
                                    <td>
                                        <strong>#{{ $cita->id }}</strong>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-user me-2 text-muted"></i>
                                            <div>
                                                <strong>{{ $cita->solicitante->name }}</strong><br>
                                                <small class="text-muted">{{ $cita->solicitante->email }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if($cita->estudianteReferido)
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-graduation-cap me-2 text-primary"></i>
                                                <div>
                                                    <strong>{{ $cita->estudianteReferido->name }}</strong><br>
                                                    <small class="text-muted">{{ $cita->estudianteReferido->email }}</small>
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-muted">No especificado</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            {{ $cita->tipo_cita_formateado }}
                                        </span>
                                        <br>
                                        <small class="text-muted">{{ $cita->modalidad_formateada }}</small>
                                    </td>
                                    <td>
                                        @if($cita->fecha_asignada)
                                            <div>
                                                <i class="fas fa-calendar me-1"></i>
                                                <strong>{{ $cita->fecha_asignada->format('d/m/Y') }}</strong>
                                            </div>
                                            <div>
                                                <i class="fas fa-clock me-1"></i>
                                                <strong>{{ $cita->hora_asignada }}</strong>
                                                <small class="text-muted">({{ $cita->duracion_formateada }})</small>
                                            </div>
                                        @else
                                            <div class="text-muted">
                                                <small>Solicitada para:</small><br>
                                                {{ $cita->fecha_solicitada->format('d/m/Y') }} {{ $cita->hora_solicitada }}
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        @if($cita->orientador)
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-user-tie me-2 text-success"></i>
                                                <div>
                                                    <strong>{{ $cita->orientador->name }}</strong><br>
                                                    <small class="text-muted">{{ $cita->orientador->email }}</small>
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-muted">Sin asignar</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $badgeClass = match($cita->estado) {
                                                'solicitada' => 'bg-warning',
                                                'programada' => 'bg-info',
                                                'confirmada' => 'bg-primary',
                                                'en_curso' => 'bg-warning',
                                                'completada' => 'bg-success',
                                                'cancelada' => 'bg-danger',
                                                'reprogramada' => 'bg-secondary',
                                                default => 'bg-secondary'
                                            };
                                        @endphp
                                        <span class="badge {{ $badgeClass }}">
                                            {{ $cita->estado_formateado }}
                                        </span>
                                    </td>
                                    <td>
                                        @php
                                            $prioridadClass = match($cita->prioridad) {
                                                'baja' => 'text-success',
                                                'media' => 'text-warning',
                                                'alta' => 'text-orange',
                                                'urgente' => 'text-danger',
                                                default => 'text-secondary'
                                            };
                                        @endphp
                                        <span class="{{ $prioridadClass }}">
                                            <i class="fas fa-exclamation-triangle me-1"></i>
                                            {{ $cita->prioridad_formateada }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('citas.show', $cita) }}" class="btn btn-sm btn-outline-primary" title="Ver detalles">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            
                                            @if($cita->estado === 'solicitada' && 
                                                (auth()->user()->id === $cita->solicitante_id || auth()->user()->roles->nombre === 'Rector'))
                                                <a href="{{ route('citas.edit', $cita) }}" class="btn btn-sm btn-outline-warning" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            @endif

                                            @if($cita->puedeSerCancelada() && 
                                                (auth()->user()->id === $cita->solicitante_id || 
                                                 auth()->user()->id === $cita->orientador_id || 
                                                 auth()->user()->roles->nombre === 'Rector'))
                                                <button type="button" class="btn btn-sm btn-outline-danger btn-cancelar-cita" 
                                                        data-cita-id="{{ $cita->id }}" title="Cancelar">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center py-5">
                                        <div class="d-flex flex-column align-items-center">
                                            <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                            <h5 class="text-muted">No hay citas registradas</h5>
                                            <p class="text-muted">Comience creando una nueva cita</p>
                                            <a href="{{ route('citas.create') }}" class="btn btn-primary">
                                                <i class="fas fa-plus me-2"></i>Crear Primera Cita
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Paginación --}}
                    @if($citas->hasPages())
                        <div class="d-flex justify-content-center mt-4">
                            {{ $citas->appends(request()->query())->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal para cancelar cita --}}
<div class="modal fade" id="cancelarCitaModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cancelar Cita</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="cancelarCitaForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="motivo_cancelacion" class="form-label">Motivo de la cancelación <span class="text-danger">*</span></label>
                        <textarea name="motivo_cancelacion" id="motivo_cancelacion" class="form-control" rows="3" 
                                  placeholder="Explique el motivo de la cancelación..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-danger">Cancelar Cita</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Manejar botones de cancelar cita
    document.querySelectorAll('.btn-cancelar-cita').forEach(function(button) {
        button.addEventListener('click', function() {
            const citaId = this.getAttribute('data-cita-id');
            const modal = new bootstrap.Modal(document.getElementById('cancelarCitaModal'));
            const form = document.getElementById('cancelarCitaForm');
            form.action = `/citas/${citaId}/cancelar`;
            modal.show();
        });
    });
});

// Auto-refresh cada 5 minutos para mantener actualizado el estado de las citas
setInterval(function() {
    if (!document.hidden) {
        location.reload();
    }
}, 300000); // 5 minutos
</script>
@endsection