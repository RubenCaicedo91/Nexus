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
                                {{-- Estado filter removed per request --}}

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

                                @php
                                    $currentRole = strtolower(trim((string) (optional(auth()->user()->role)->nombre ?? '')));
                                    $isAcudiente = $currentRole === 'acudiente';
                                    $isOrientador = $currentRole === 'orientador';
                                @endphp

                                @if(! $isAcudiente)
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

                    {{-- Estadísticas por estado eliminadas --}}

                    {{-- Tabla de citas --}}
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" style="table-layout:fixed; width:100%;">
                            <thead class="table-dark">
                                    <tr>
                                        <th>Solicitante</th>
                                        <th>Estudiante</th>
                                        <th>Tipo</th>
                                        <th>Fecha/Hora</th>
                                        <th>Orientador</th>
                                        <th>Prioridad</th>
                                           <th>Estado</th>

                                            @if($esSolicitante)
                                                @if(!empty($cita->resumen_cita) && ($esSolicitante || $esAcudienteDelEstudiante || $isOrientador))
                                                        <a href="{{ route('citas.show', $cita) }}" class="btn btn-sm btn-success open-cita" title="Revisión">
                                                            <i class="fas fa-sticky-note"></i>
                                                        </a>
                                                @endif
                                            @elseif($isOrientador)
                                                <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#atenderModal-{{ $cita->id }}" title="Atendió">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#noAsistioModal-{{ $cita->id }}" title="No atendió">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                                @if(!empty($cita->resumen_cita) && ($esSolicitante || $esAcudienteDelEstudiante || $isOrientador))
                                                    <a href="{{ route('citas.show', $cita) }}" class="btn btn-sm btn-success open-cita" title="Revisión de notas">
                                                        <i class="fas fa-sticky-note"></i>
                                                    </a>
                                                @endif
                                                @else
                                                @if(!empty($cita->resumen_cita) && ($esSolicitante || $esAcudienteDelEstudiante || $isOrientador))
                                                    <a href="{{ route('citas.show', $cita) }}" class="btn btn-sm btn-success open-cita" title="Revisión">
                                                        <i class="fas fa-sticky-note"></i>
                                                    </a>
                                                @else
                                                    <a href="{{ route('citas.show', $cita) }}" class="btn btn-sm btn-outline-primary" title="Ver detalles">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                @endif
                                            @endif
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
                                        @if($cita->esCompletada())
                                            <span class="badge bg-success">Atendido</span>
                                        @elseif($cita->esCancelada())
                                            <span class="badge bg-danger">No atendido</span>
                                        @elseif($cita->children && $cita->children->count() > 0)
                                            <span class="badge bg-info">Con seguimiento</span>
                                        @else
                                            <span class="badge bg-secondary">Sin acción aún</span>
                                        @endif
                                    </td>
                                    <td style="width:240px; max-width:240px; white-space:normal; overflow:hidden;">
                                        <div style="display:flex; flex-wrap:wrap; gap:.5rem; width:100%;">
                                            {{-- Inline buttons for small+ screens --}}
                                            <div class="d-none d-sm-flex gap-2 w-100">
                                                @if($esSolicitante)
                                                    @if(!empty($cita->resumen_cita) && ($esSolicitante || $esAcudienteDelEstudiante || $isOrientador))
                                                        <a href="{{ route('citas.show', $cita) }}" class="btn btn-sm btn-success open-cita" title="Revisión">
                                                            <i class="fas fa-sticky-note"></i>
                                                        </a>
                                                    @endif
                                                @elseif($isOrientador)
                                                    <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#atenderModal-{{ $cita->id }}" title="Atendió">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#noAsistioModal-{{ $cita->id }}" title="No atendió">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                    @if(!empty($cita->resumen_cita) && ($esSolicitante || $esAcudienteDelEstudiante || $isOrientador))
                                                        <a href="{{ route('citas.show', $cita) }}" class="btn btn-sm btn-success open-cita" title="Revisión de notas">
                                                            <i class="fas fa-sticky-note"></i>
                                                        </a>
                                                    @endif
                                                @else
                                                    @if(!empty($cita->resumen_cita) && ($esSolicitante || $esAcudienteDelEstudiante || $isOrientador))
                                                        <a href="{{ route('citas.show', $cita) }}" class="btn btn-sm btn-success open-cita" title="Revisión">
                                                            <i class="fas fa-sticky-note"></i>
                                                        </a>
                                                    @else
                                                        <a href="{{ route('citas.show', $cita) }}" class="btn btn-sm btn-outline-primary" title="Ver detalles">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    @endif
                                                @endif
                                                {{-- Botón Motivo cuando la cita fue marcada como no atendida --}}
                                                @if($cita->esCancelada() && !empty($cita->motivo_cancelacion))
                                                    <button type="button" class="btn btn-sm btn-outline-secondary ms-1 ver-motivo" data-motivo="{{ e($cita->motivo_cancelacion) }}" title="Ver motivo">
                                                        <i class="fas fa-comment-alt"></i>
                                                    </button>
                                                @endif
                                            </div>

                                            {{-- Dropdown for xs screens --}}
                                            <div class="d-sm-none dropdown w-100">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle w-100" type="button" id="accionesDropdown{{ $cita->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                                                    Acciones
                                                </button>
                                                <ul class="dropdown-menu" aria-labelledby="accionesDropdown{{ $cita->id }}">
                                                    @if($esSolicitante)
                                                        @if(!empty($cita->resumen_cita) && ($esSolicitante || $esAcudienteDelEstudiante || $isOrientador))
                                                                <li><a class="dropdown-item open-cita text-success" href="{{ route('citas.show', $cita) }}">Revisión</a></li>
                                                            @endif
                                                    @elseif($isOrientador)
                                                        <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#atenderModal-{{ $cita->id }}">Atendió</a></li>
                                                        <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#noAsistioModal-{{ $cita->id }}">No atendió</a></li>
                                                        @if(!empty($cita->resumen_cita) && ($esSolicitante || $esAcudienteDelEstudiante || $isOrientador))
                                                            <li><a class="dropdown-item open-cita text-success" href="{{ route('citas.show', $cita) }}">Revisión de notas</a></li>
                                                        @endif
                                                    @else
                                                        @if(!empty($cita->resumen_cita) && ($esSolicitante || $esAcudienteDelEstudiante || $isOrientador))
                                                            <li><a class="dropdown-item open-cita text-success" href="{{ route('citas.show', $cita) }}">Revisión</a></li>
                                                        @else
                                                            <li><a class="dropdown-item" href="{{ route('citas.show', $cita) }}">Ver detalles</a></li>
                                                        @endif
                                                    @endif
                                                    @if($cita->esCancelada() && !empty($cita->motivo_cancelacion))
                                                        <li><a class="dropdown-item open-cita text-danger" href="{{ route('citas.show', $cita) }}">Motivo</a></li>
                                                    @endif
                                                </ul>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @if(!empty($cita->resumen_cita) && ($esSolicitante || $esAcudienteDelEstudiante || $isOrientador))
                                    {{-- per-row modal removed in favor of AJAX modal --}}
                                @endif
                                @empty
                                <tr>
                                       <td colspan="8" class="text-center py-5">
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

<!-- Modal: Mostrar motivo completo -->
<div class="modal fade" id="motivoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Motivo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body" id="motivoModalBody" style="white-space:pre-wrap;"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
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

    // inicializar tooltips (para motivos truncados)
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.forEach(function (el) { new bootstrap.Tooltip(el); });

    // manejar clicks en 'Ver motivo' para abrir modal con texto completo
    document.querySelectorAll('.ver-motivo').forEach(function(btn){
        btn.addEventListener('click', function(e){
            e.preventDefault();
            var motivo = this.getAttribute('data-motivo') || '';
            var body = document.getElementById('motivoModalBody');
            if(body) body.textContent = motivo;
            var modalEl = document.getElementById('motivoModal');
            if(modalEl) {
                var modal = new bootstrap.Modal(modalEl);
                modal.show();
            }
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