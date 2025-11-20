@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-calendar-alt me-2"></i>Detalles de la Cita #{{ $cita->id }}
                    </h4>
                        <div class="d-flex gap-2">
                        <a href="{{ url('gestion-orientacion/citas') }}" class="btn btn-light">
                            <i class="fas fa-arrow-left me-2"></i>Volver al Listado
                        </a>
                        @if($cita->estado === 'solicitada' && auth()->user()->id === $cita->solicitante_id)
                            <a href="{{ route('citas.edit', $cita) }}" class="btn btn-warning">
                                <i class="fas fa-edit me-2"></i>Editar
                            </a>
                        @endif
                    </div>
                </div>

                <div class="card-body">
                    {{-- Estado y acciones rápidas --}}
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <div class="d-flex align-items-center gap-3">
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
                                    
                                    $prioridadClass = match($cita->prioridad) {
                                        'baja' => 'text-success',
                                        'media' => 'text-warning',
                                        'alta' => 'text-orange',
                                        'urgente' => 'text-danger',
                                        default => 'text-secondary'
                                    };
                                @endphp
                                
                                <span class="badge {{ $badgeClass }} fs-6">
                                    {{ $cita->estado_formateado }}
                                </span>
                                
                                {{-- Prioridad ocultada por petición de UX (no necesaria en esta vista) --}}
                                
                                <span class="badge bg-secondary">
                                    {{ $cita->tipo_cita_formateado }}
                                </span>
                                
                                <span class="badge bg-dark">
                                    {{ $cita->modalidad_formateada }}
                                </span>
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="btn-group" role="group">
                                {{-- Acciones según estado y rol --}}
                                
                                @php
                                    // Detección robusta del rol actual
                                    $currentRole = strtolower(trim((string) (optional(auth()->user()->role)->nombre ?? '')));
                                    $isOrientador = $currentRole === 'orientador';
                                    $isSolicitante = auth()->user()->id === $cita->solicitante_id;
                                @endphp

                                @if($cita->estado === 'solicitada' && $isOrientador)
                                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#programarModal">
                                        <i class="fas fa-calendar-check me-1"></i>Programar
                                    </button>
                                @endif

                                @if($cita->estado === 'programada' && $isSolicitante)
                                    <form action="{{ route('citas.confirmar', $cita) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-check me-1"></i>Confirmar Asistencia
                                        </button>
                                    </form>
                                @endif

                                @if($isOrientador && in_array($cita->estado, ['programada', 'confirmada']))
                                    <!-- Botón: Registrar que se atendió (abre modal para observaciones) -->
                                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#atenderModal">
                                        <i class="fas fa-user-check me-1"></i>Atendió
                                    </button>

                                    <!-- Botón: Registrar que no asistió (abre modal para motivo) -->
                                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#noAsistioModal">
                                        <i class="fas fa-user-times me-1"></i>No atendió
                                    </button>
                                @endif

                                @if($isOrientador && in_array($cita->estado, ['programada', 'confirmada']))
                                    <form action="{{ route('citas.iniciar', $cita) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-warning">
                                            <i class="fas fa-play me-1"></i>Iniciar Cita
                                        </button>
                                    </form>
                                @endif

                                @if($cita->puedeSerCancelada() && 
                                    (auth()->user()->id === $cita->solicitante_id || 
                                     auth()->user()->id === $cita->orientador_id))
                                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#cancelarModal">
                                        <i class="fas fa-times me-1"></i>Cancelar
                                    </button>
                                @endif

                                @if($cita->puedeSerReprogramada() && $isOrientador)
                                    <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#reprogramarModal">
                                        <i class="fas fa-calendar me-1"></i>Reprogramar
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        {{-- Información de la solicitud --}}
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">
                                        <i class="fas fa-info-circle me-2"></i>Información de la Solicitud
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td><strong>Solicitante:</strong></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-user me-2 text-primary"></i>
                                                    <div>
                                                        {{ $cita->solicitante->name }}<br>
                                                        <small class="text-muted">{{ $cita->solicitante->email }}</small>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        @php
                                            $solicitanteRole = strtolower(trim((string) (optional($cita->solicitante->role)->nombre ?? '')));
                                            $isSolicitanteAcudiente = $solicitanteRole === 'acudiente';
                                        @endphp

                                        @if($isSolicitanteAcudiente)
                                        <tr>
                                            <td><strong>Estudiante Referido:</strong></td>
                                            <td>
                                                @if($cita->estudianteReferido)
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-graduation-cap me-2 text-success"></i>
                                                        <div>
                                                            {{ $cita->estudianteReferido->name }}<br>
                                                            <small class="text-muted">{{ $cita->estudianteReferido->email }}</small>
                                                        </div>
                                                    </div>
                                                @else
                                                    <span class="text-muted">No especificado</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @endif
                                        <tr>
                                            <td><strong>Motivo:</strong></td>
                                            <td>{{ $cita->motivo }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Fecha Solicitada:</strong></td>
                                            <td>
                                                <i class="fas fa-calendar me-1"></i>
                                                {{ $cita->fecha_solicitada->format('d/m/Y') }} a las {{ $cita->hora_solicitada }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Duración:</strong></td>
                                            <td>{{ $cita->duracion_formateada }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                        {{-- Información de programación --}}
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">
                                        <i class="fas fa-calendar-check me-2"></i>Información de Programación
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td><strong>Orientador:</strong></td>
                                            <td>
                                                @if($cita->orientador)
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-user-tie me-2 text-info"></i>
                                                        <div>
                                                            {{ $cita->orientador->name }}<br>
                                                            <small class="text-muted">{{ $cita->orientador->email }}</small>
                                                        </div>
                                                    </div>
                                                @else
                                                    <span class="text-muted">Sin asignar</span>
                                                @endif
                                            </td>
                                        </tr>
                                        
                                        <tr>
                                            <td><strong>Creada:</strong></td>
                                            <td>
                                                <small class="text-muted">
                                                    {{ $cita->created_at->format('d/m/Y H:i') }}
                                                </small>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Descripción y observaciones --}}
                    <div class="row">
                        <div class="col-12">
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">
                                        <i class="fas fa-clipboard me-2"></i>Descripción y Observaciones
                                    </h5>
                                </div>
                                <div class="card-body">
                                    @if($cita->descripcion)
                                        <div class="mb-3">
                                            <h6><strong>Descripción:</strong></h6>
                                            <p class="text-muted">{{ $cita->descripcion }}</p>
                                        </div>
                                    @endif

                                    @if($cita->observaciones_previas)
                                        <div class="mb-3">
                                            <h6><strong>Observaciones Previas:</strong></h6>
                                            <p class="text-muted">{{ $cita->observaciones_previas }}</p>
                                        </div>
                                    @endif

                                    @if($cita->instrucciones_adicionales)
                                        <div class="mb-3">
                                            <h6><strong>Instrucciones Adicionales:</strong></h6>
                                            <p class="text-muted">{{ $cita->instrucciones_adicionales }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Resumen de la cita (si está completada) --}}
                    @php
                        $esSolicitante = auth()->id() === $cita->solicitante_id;
                        $esOrientador = auth()->id() === $cita->orientador_id;
                        $esEstudianteReferido = auth()->id() === optional($cita->estudianteReferido)->id;
                        $esAcudienteDelEstudiante = optional($cita->estudianteReferido)->acudiente_id === auth()->id();
                    @endphp
                    @if($cita->esCompletada() && ($esSolicitante || $esOrientador || $esEstudianteReferido || $esAcudienteDelEstudiante))
                        <div class="row">
                            <div class="col-12">
                                <div class="card mb-4 border-success">
                                    <div class="card-header bg-success text-white">
                                        <h5 class="mb-0">
                                            <i class="fas fa-check-circle me-2"></i>Resumen de la Cita Completada
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        @if($cita->resumen_cita)
                                            <div class="mb-3">
                                                <h6><strong>Resumen:</strong></h6>
                                                <p>{{ $cita->resumen_cita }}</p>
                                            </div>
                                        @endif

                                        @if($cita->recomendaciones)
                                            <div class="mb-3">
                                                <h6><strong>Recomendaciones:</strong></h6>
                                                <p>{{ $cita->recomendaciones }}</p>
                                            </div>
                                        @endif

                                        @if($cita->plan_seguimiento)
                                            <div class="mb-3">
                                                <h6><strong>Plan de Seguimiento:</strong></h6>
                                                <p>{{ $cita->plan_seguimiento }}</p>
                                            </div>
                                        @endif

                                        @if($cita->requiere_seguimiento && $cita->fecha_seguimiento)
                                            <div class="alert alert-info">
                                                <i class="fas fa-calendar-plus me-2"></i>
                                                <strong>Seguimiento programado para:</strong>
                                                {{ optional($cita->fecha_seguimiento)->format('d/m/Y') }}
                                                @if(!empty($cita->hora_seguimiento))
                                                    a las {{ $cita->hora_seguimiento }}
                                                @endif
                                            </div>
                                        @endif
                                        @if($cita->children && $cita->children->count() > 0)
                                            <div class="mb-3">
                                                <h6><strong>Seguimientos asociados:</strong></h6>
                                                <ul>
                                                    @foreach($cita->children as $child)
                                                        <li>
                                                            <a href="{{ route('citas.show', $child) }}">Cita #{{ $child->id }}</a>
                                                            - {{ $child->estado_formateado }}
                                                            @if($child->fecha_asignada)
                                                                ({{ $child->fecha_asignada->format('d/m/Y') }} @if($child->hora_asignada) a las {{ $child->hora_asignada }} @endif)
                                                            @endif
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Información de cancelación --}}
                    @if($cita->esCancelada() && (auth()->id() === $cita->solicitante_id || auth()->id() === $cita->orientador_id))
                        <div class="row">
                            <div class="col-12">
                                <div class="card mb-4 border-danger">
                                    <div class="card-header bg-danger text-white">
                                        <h5 class="mb-0">
                                            <i class="fas fa-times-circle me-2"></i>Información de Cancelación
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <p><strong>Motivo:</strong> {{ $cita->motivo_cancelacion }}</p>
                                        <p><strong>Cancelada por:</strong> {{ $cita->canceladoPor->name ?? 'Usuario no disponible' }}</p>
                                        <p><strong>Fecha de cancelación:</strong> {{ $cita->fecha_cancelacion->format('d/m/Y H:i') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal para programar cita --}}
@php
    $__currentRole = strtolower(trim((string) (optional(auth()->user()->role)->nombre ?? '')));
    $__isOrientador = $__currentRole === 'orientador';
@endphp
@if($cita->estado === 'solicitada' && $__isOrientador)
<div class="modal fade" id="programarModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Programar Cita</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('citas.programar', $cita) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="fecha_asignada" class="form-label">Fecha Asignada <span class="text-danger">*</span></label>
                                <input type="date" name="fecha_asignada" id="fecha_asignada" class="form-control" 
                                       value="{{ $cita->fecha_solicitada->format('Y-m-d') }}" min="{{ date('Y-m-d') }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="hora_asignada" class="form-label">Hora Asignada <span class="text-danger">*</span></label>
                                <input type="time" name="hora_asignada" id="hora_asignada" class="form-control" 
                                       value="{{ $cita->hora_solicitada }}" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="orientador_id" class="form-label">Orientador Asignado <span class="text-danger">*</span></label>
                        <select name="orientador_id" id="orientador_id" class="form-select" required>
                            @foreach($orientadores ?? [] as $orientador)
                                <option value="{{ $orientador->id }}" {{ $cita->orientador_id == $orientador->id ? 'selected' : '' }}>
                                    {{ $orientador->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    @if($cita->modalidad === 'presencial')
                        <div class="mb-3">
                            <label for="lugar_cita" class="form-label">Lugar de la Cita</label>
                            <input type="text" name="lugar_cita" id="lugar_cita" class="form-control" 
                                   value="{{ $cita->lugar_cita }}" placeholder="Especifique el lugar...">
                        </div>
                    @elseif($cita->modalidad === 'virtual')
                        <div class="mb-3">
                            <label for="link_virtual" class="form-label">Link Virtual</label>
                            <input type="url" name="link_virtual" id="link_virtual" class="form-control" 
                                   value="{{ $cita->link_virtual }}" placeholder="https://meet.google.com/...">
                        </div>
                    @endif

                    <div class="mb-3">
                        <label for="instrucciones_adicionales" class="form-label">Instrucciones Adicionales</label>
                        <textarea name="instrucciones_adicionales" id="instrucciones_adicionales" class="form-control" 
                                  rows="3">{{ $cita->instrucciones_adicionales }}</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-success">Programar Cita</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- Modal para completar cita --}}
{{-- Modal para atender: Orientador registra observaciones (visible sólo para solicitante y orientador) --}}
@if(in_array($cita->estado, ['programada','confirmada']) && auth()->user()->id === $cita->orientador_id)
<div class="modal fade" id="atenderModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Registrar Atención de la Cita</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('citas.completar', $cita) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="resumen_cita" class="form-label">Observaciones / Resumen <span class="text-danger">*</span></label>
                        <textarea name="resumen_cita" id="resumen_cita" class="form-control" rows="5" required placeholder="Describa lo realizado en la atención..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="recomendaciones" class="form-label">Recomendaciones</label>
                        <textarea name="recomendaciones" id="recomendaciones" class="form-control" rows="3"></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="plan_seguimiento" class="form-label">Plan de seguimiento (opcional)</label>
                        <textarea name="plan_seguimiento" id="plan_seguimiento" class="form-control" rows="3"></textarea>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" value="1" id="requiere_seguimiento" name="requiere_seguimiento">
                        <label class="form-check-label" for="requiere_seguimiento">Requiere seguimiento</label>
                    </div>

                    <div class="mb-3">
                        <label for="fecha_seguimiento" class="form-label">Fecha de seguimiento (si aplica)</label>
                        <input type="date" name="fecha_seguimiento" id="fecha_seguimiento" class="form-control" min="{{ date('Y-m-d', strtotime('+1 day')) }}">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-success">Guardar y Marcar como Atendida</button>
                </div>
            </form>
        </div>
    </div>
 </div>
@endif

{{-- Modal para "No atendió": Orientador registra motivo de no atención --}}
@if(in_array($cita->estado, ['programada','confirmada']) && auth()->user()->id === $cita->orientador_id)
<div class="modal fade" id="noAsistioModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Registrar No Atención</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('citas.cancelar', $cita) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="motivo_no_asistencia" class="form-label">Motivo por el cual no se atendió la cita <span class="text-danger">*</span></label>
                        <textarea name="motivo_cancelacion" id="motivo_no_asistencia" class="form-control" rows="4" required placeholder="Explique por qué no se atendió la cita..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-danger">Registrar No Atención</button>
                </div>
            </form>
        </div>
    </div>
 </div>
@endif

{{-- Modal para cancelar cita --}}
@if($cita->puedeSerCancelada())
<div class="modal fade" id="cancelarModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cancelar Cita</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('citas.cancelar', $cita) }}" method="POST">
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
@endif

{{-- Modal para reprogramar cita --}}
@if($cita->puedeSerReprogramada())
<div class="modal fade" id="reprogramarModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reprogramar Cita</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('citas.reprogramar', $cita) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="fecha_asignada_repro" class="form-label">Nueva Fecha <span class="text-danger">*</span></label>
                                <input type="date" name="fecha_asignada" id="fecha_asignada_repro" class="form-control" 
                                       min="{{ date('Y-m-d') }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="hora_asignada_repro" class="form-label">Nueva Hora <span class="text-danger">*</span></label>
                                <input type="time" name="hora_asignada" id="hora_asignada_repro" class="form-control" required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-warning">Reprogramar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<script>
// Auto-refresh de la página cada 2 minutos para mantener actualizado el estado
setInterval(function() {
    if (!document.hidden) {
        location.reload();
    }
}, 120000); // 2 minutos
</script>

{{-- Floating actions removed per request --}}
@endsection