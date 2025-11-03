@extends('layouts.app')

@section('title', 'Detalle de Pensión')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Encabezado -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-1">
                        <i class="fas fa-money-bill-wave text-primary me-2"></i>
                        Detalle de Pensión
                    </h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('pensiones.index') }}">Pensiones</a></li>
                            <li class="breadcrumb-item active">Detalle</li>
                        </ol>
                    </nav>
                </div>
                <div class="btn-group">
                    <a href="{{ route('pensiones.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                    
                    @if(!$pension->isPagada() && !$pension->isAnulada())
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#pagoModal">
                            <i class="fas fa-money-bill"></i> Procesar Pago
                        </button>
                    @endif

                    @if(auth()->user()->roles_id != 4 && !$pension->isPagada() && !$pension->isAnulada())
                        <a href="{{ route('pensiones.edit', $pension) }}" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Editar
                        </a>
                    @endif

                    @if(auth()->user()->roles_id != 4 && !$pension->isAnulada())
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#anularModal">
                            <i class="fas fa-ban"></i> Anular
                        </button>
                    @endif
                </div>
            </div>

            <div class="row">
                <!-- Información Principal -->
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-info-circle me-2"></i>
                                Información de la Pensión
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="text-muted mb-3">Información Académica</h6>
                                    
                                    <div class="mb-3">
                                        <label class="fw-bold text-dark">Estudiante:</label>
                                        <div class="d-flex align-items-center mt-1">
                                            <div class="avatar bg-primary rounded-circle me-3" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-user text-white"></i>
                                            </div>
                                            <div>
                                                <div class="fw-medium">{{ $pension->estudiante->name }}</div>
                                                <small class="text-muted">{{ $pension->estudiante->email }}</small>
                                            </div>
                                        </div>
                                    </div>

                                    @if($pension->acudiente && auth()->user()->roles_id != 4)
                                        <div class="mb-3">
                                            <label class="fw-bold text-dark">Acudiente:</label>
                                            <div class="d-flex align-items-center mt-1">
                                                <div class="avatar bg-info rounded-circle me-3" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                                    <i class="fas fa-user-friends text-white"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-medium">{{ $pension->acudiente->name }}</div>
                                                    <small class="text-muted">{{ $pension->acudiente->email }}</small>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    <div class="mb-3">
                                        <label class="fw-bold text-dark">Curso/Grado:</label>
                                        <p class="mb-0">
                                            @if($pension->curso)
                                                {{ $pension->curso->nombre }}
                                                <span class="badge bg-info ms-2">{{ $pension->grado }}</span>
                                            @else
                                                <span class="badge bg-secondary">{{ $pension->grado ?: 'N/A' }}</span>
                                            @endif
                                        </p>
                                    </div>

                                    <div class="mb-3">
                                        <label class="fw-bold text-dark">Concepto:</label>
                                        <p class="mb-0">{{ $pension->concepto }}</p>
                                    </div>

                                    <div class="mb-3">
                                        <label class="fw-bold text-dark">Período:</label>
                                        <p class="mb-0">
                                            <i class="fas fa-calendar me-1"></i>
                                            {{ DateTime::createFromFormat('!m', $pension->mes)->format('F') }} {{ $pension->año }}
                                        </p>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <h6 class="text-muted mb-3">Información Financiera</h6>
                                    
                                    <div class="mb-3">
                                        <label class="fw-bold text-dark">Valor Base:</label>
                                        <p class="mb-0 h5 text-primary">${{ number_format($pension->valor_base, 0) }}</p>
                                    </div>

                                    @if($pension->descuentos > 0)
                                        <div class="mb-3">
                                            <label class="fw-bold text-dark">Descuentos:</label>
                                            <p class="mb-0 h6 text-success">-${{ number_format($pension->descuentos, 0) }}</p>
                                        </div>
                                    @endif

                                    @if($pension->recargos > 0)
                                        <div class="mb-3">
                                            <label class="fw-bold text-dark">Recargos:</label>
                                            <p class="mb-0 h6 text-warning">+${{ number_format($pension->recargos, 0) }}</p>
                                        </div>
                                    @endif

                                    @if($pension->recargo_mora > 0)
                                        <div class="mb-3">
                                            <label class="fw-bold text-dark">Recargo por Mora:</label>
                                            <p class="mb-0 h6 text-danger">+${{ number_format($pension->recargo_mora, 0) }}</p>
                                            @if($pension->fecha_recargo)
                                                <small class="text-muted">Aplicado: {{ \Carbon\Carbon::parse($pension->fecha_recargo)->format('d/m/Y H:i') }}</small>
                                            @endif
                                        </div>
                                    @endif

                                    <hr>
                                    <div class="mb-3">
                                        <label class="fw-bold text-dark">Valor Total:</label>
                                        <p class="mb-0 h4 text-primary fw-bold">${{ number_format($pension->valor_total, 0) }}</p>
                                    </div>

                                    <div class="mb-3">
                                        <label class="fw-bold text-dark">Fecha de Vencimiento:</label>
                                        <p class="mb-0">
                                            <i class="fas fa-calendar-times me-1"></i>
                                            {{ \Carbon\Carbon::parse($pension->fecha_vencimiento)->format('d/m/Y') }}
                                            @if($pension->isVencida() && !$pension->isPagada())
                                                <span class="badge bg-danger ms-2">
                                                    Vencida hace {{ $pension->diasVencida() }} días
                                                </span>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </div>

                            @if($pension->observaciones)
                                <hr>
                                <div class="mb-0">
                                    <label class="fw-bold text-dark">Observaciones:</label>
                                    <div class="bg-light p-3 rounded mt-2">
                                        {{ $pension->observaciones }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Información de Pago (si está pagada) -->
                    @if($pension->isPagada())
                        <div class="card mt-4">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-check-circle me-2"></i>
                                    Información del Pago
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="fw-bold text-dark">Fecha de Pago:</label>
                                            <p class="mb-0">
                                                <i class="fas fa-calendar-check me-1 text-success"></i>
                                                {{ \Carbon\Carbon::parse($pension->fecha_pago)->format('d/m/Y H:i') }}
                                            </p>
                                        </div>

                                        <div class="mb-3">
                                            <label class="fw-bold text-dark">Método de Pago:</label>
                                            <p class="mb-0">
                                                @switch($pension->metodo_pago)
                                                    @case('efectivo')
                                                        <i class="fas fa-money-bill me-1"></i> Efectivo
                                                        @break
                                                    @case('transferencia')
                                                        <i class="fas fa-exchange-alt me-1"></i> Transferencia Bancaria
                                                        @break
                                                    @case('tarjeta')
                                                        <i class="fas fa-credit-card me-1"></i> Tarjeta de Crédito/Débito
                                                        @break
                                                    @case('consignacion')
                                                        <i class="fas fa-university me-1"></i> Consignación
                                                        @break
                                                    @case('pse')
                                                        <i class="fas fa-laptop me-1"></i> PSE
                                                        @break
                                                    @default
                                                        {{ $pension->metodo_pago }}
                                                @endswitch
                                            </p>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="fw-bold text-dark">Número de Recibo:</label>
                                            <p class="mb-0">
                                                <code>{{ $pension->numero_recibo }}</code>
                                            </p>
                                        </div>

                                        @if($pension->procesadoPor)
                                            <div class="mb-3">
                                                <label class="fw-bold text-dark">Procesado por:</label>
                                                <p class="mb-0">{{ $pension->procesadoPor->name }}</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                @if($pension->comprobante_pago)
                                    <div class="mb-3">
                                        <label class="fw-bold text-dark">Comprobante de Pago:</label>
                                        <div class="mt-2">
                                            <a href="{{ Storage::url($pension->comprobante_pago) }}" target="_blank" class="btn btn-outline-primary">
                                                <i class="fas fa-file-download me-1"></i>
                                                Descargar Comprobante
                                            </a>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Panel de Estado y Acciones -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-flag me-2"></i>
                                Estado de la Pensión
                            </h5>
                        </div>
                        <div class="card-body text-center">
                            @switch($pension->estado)
                                @case('pendiente')
                                    <div class="mb-3">
                                        <i class="fas fa-clock fa-3x text-warning mb-2"></i>
                                        <h5 class="text-warning">PENDIENTE</h5>
                                        <p class="text-muted">Esta pensión está pendiente de pago</p>
                                    </div>
                                    @break
                                @case('pagada')
                                    <div class="mb-3">
                                        <i class="fas fa-check-circle fa-3x text-success mb-2"></i>
                                        <h5 class="text-success">PAGADA</h5>
                                        <p class="text-muted">Pago procesado exitosamente</p>
                                    </div>
                                    @break
                                @case('vencida')
                                    <div class="mb-3">
                                        <i class="fas fa-exclamation-triangle fa-3x text-danger mb-2"></i>
                                        <h5 class="text-danger">VENCIDA</h5>
                                        <p class="text-muted">Esta pensión está vencida desde hace {{ $pension->diasVencida() }} días</p>
                                        @if($pension->calcularRecargo() > 0)
                                            <div class="alert alert-warning">
                                                <strong>Recargo por mora:</strong><br>
                                                ${{ number_format($pension->calcularRecargo(), 0) }}
                                            </div>
                                        @endif
                                    </div>
                                    @break
                                @case('anulada')
                                    <div class="mb-3">
                                        <i class="fas fa-ban fa-3x text-secondary mb-2"></i>
                                        <h5 class="text-secondary">ANULADA</h5>
                                        <p class="text-muted">Esta pensión ha sido anulada</p>
                                    </div>
                                    @break
                            @endswitch

                            <!-- Información adicional -->
                            <div class="border-top pt-3 mt-3">
                                <div class="row text-start">
                                    <div class="col-12 mb-2">
                                        <small class="text-muted">Creada:</small>
                                        <br>
                                        <small>{{ $pension->created_at->format('d/m/Y H:i') }}</small>
                                    </div>
                                    @if($pension->updated_at != $pension->created_at)
                                        <div class="col-12 mb-2">
                                            <small class="text-muted">Actualizada:</small>
                                            <br>
                                            <small>{{ $pension->updated_at->format('d/m/Y H:i') }}</small>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Historial/Timeline -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-history me-2"></i>
                                Historial
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="timeline">
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-primary"></div>
                                    <div class="timeline-content">
                                        <h6 class="mb-1">Pensión Creada</h6>
                                        <small class="text-muted">{{ $pension->created_at->format('d/m/Y H:i') }}</small>
                                    </div>
                                </div>

                                @if($pension->isPagada())
                                    <div class="timeline-item">
                                        <div class="timeline-marker bg-success"></div>
                                        <div class="timeline-content">
                                            <h6 class="mb-1">Pago Procesado</h6>
                                            <small class="text-muted">{{ \Carbon\Carbon::parse($pension->fecha_pago)->format('d/m/Y H:i') }}</small>
                                            @if($pension->procesadoPor)
                                                <br><small class="text-muted">Por: {{ $pension->procesadoPor->name }}</small>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                @if($pension->isVencida())
                                    <div class="timeline-item">
                                        <div class="timeline-marker bg-danger"></div>
                                        <div class="timeline-content">
                                            <h6 class="mb-1">Pensión Vencida</h6>
                                            <small class="text-muted">{{ \Carbon\Carbon::parse($pension->fecha_vencimiento)->format('d/m/Y') }}</small>
                                        </div>
                                    </div>
                                @endif

                                @if($pension->isAnulada())
                                    <div class="timeline-item">
                                        <div class="timeline-marker bg-secondary"></div>
                                        <div class="timeline-content">
                                            <h6 class="mb-1">Pensión Anulada</h6>
                                            <small class="text-muted">{{ $pension->updated_at->format('d/m/Y H:i') }}</small>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modales -->
@if(!$pension->isPagada() && !$pension->isAnulada())
    @include('pensiones.modals.pago', ['pension' => $pension])
@endif

@if(auth()->user()->roles_id != 4 && !$pension->isAnulada())
    @include('pensiones.modals.anular', ['pension' => $pension])
@endif

@endsection

@push('styles')
<style>
    .avatar {
        flex-shrink: 0;
    }
    
    .timeline {
        position: relative;
        padding-left: 30px;
    }
    
    .timeline::before {
        content: '';
        position: absolute;
        left: 11px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #dee2e6;
    }
    
    .timeline-item {
        position: relative;
        margin-bottom: 20px;
    }
    
    .timeline-marker {
        position: absolute;
        left: -19px;
        top: 2px;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        border: 2px solid #fff;
        box-shadow: 0 0 0 2px #dee2e6;
    }
    
    .timeline-content {
        min-height: 20px;
    }
    
    .timeline-item:last-child {
        margin-bottom: 0;
    }
</style>
@endpush
