@extends('layouts.app')

@section('title', 'Editar Pensión')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-1">
                        <i class="fas fa-edit text-warning me-2"></i>
                        Editar Pensión
                    </h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('pensiones.index') }}">Pensiones</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('pensiones.show', $pension) }}">Detalle</a></li>
                            <li class="breadcrumb-item active">Editar</li>
                        </ol>
                    </nav>
                </div>
                <div class="btn-group">
                    <a href="{{ route('pensiones.show', $pension) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Cancelar
                    </a>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-form me-2"></i>
                                Editar Información de la Pensión
                            </h5>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('pensiones.update', $pension) }}" method="POST" id="formEditarPension">
                                @csrf
                                @method('PUT')
                                
                                <!-- Información del Estudiante (Solo lectura) -->
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <h6 class="text-muted mb-3">
                                            <i class="fas fa-user-graduate me-1"></i>
                                            Información del Estudiante
                                        </h6>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Estudiante</label>
                                            <div class="form-control bg-light">
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar bg-primary rounded-circle me-3" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                                                        <i class="fas fa-user text-white" style="font-size: 14px;"></i>
                                                    </div>
                                                    <div>
                                                        <strong>{{ $pension->estudiante->name }}</strong>
                                                        <br>
                                                        <small class="text-muted">{{ $pension->estudiante->email }}</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Información del Curso</label>
                                            <div class="form-control bg-light">
                                                @if($pension->curso)
                                                    <strong>Curso:</strong> {{ $pension->curso->nombre }}<br>
                                                    <strong>Grado:</strong> {{ $pension->grado }}<br>
                                                @else
                                                    <strong>Grado:</strong> {{ $pension->grado ?: 'N/A' }}
                                                @endif
                                                @if($pension->acudiente)
                                                    <strong>Acudiente:</strong> {{ $pension->acudiente->name }}
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <hr>

                                <!-- Información de la Pensión (Solo lectura para algunos campos) -->
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <h6 class="text-muted mb-3">
                                            <i class="fas fa-calendar me-1"></i>
                                            Período y Concepto
                                        </h6>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="concepto" class="form-label">Concepto <span class="text-danger">*</span></label>
                                            <select class="form-select @error('concepto') is-invalid @enderror" 
                                                    id="concepto" name="concepto" required>
                                                <option value="">Seleccionar concepto</option>
                                                <option value="Pensión Escolar" {{ old('concepto', $pension->concepto) == 'Pensión Escolar' ? 'selected' : '' }}>Pensión Escolar</option>
                                                <option value="Matrícula" {{ old('concepto', $pension->concepto) == 'Matrícula' ? 'selected' : '' }}>Matrícula</option>
                                                <option value="Seguro Estudiantil" {{ old('concepto', $pension->concepto) == 'Seguro Estudiantil' ? 'selected' : '' }}>Seguro Estudiantil</option>
                                                <option value="Material Didáctico" {{ old('concepto', $pension->concepto) == 'Material Didáctico' ? 'selected' : '' }}>Material Didáctico</option>
                                                <option value="Transporte" {{ old('concepto', $pension->concepto) == 'Transporte' ? 'selected' : '' }}>Transporte</option>
                                                <option value="Alimentación" {{ old('concepto', $pension->concepto) == 'Alimentación' ? 'selected' : '' }}>Alimentación</option>
                                                <option value="Otro" {{ old('concepto', $pension->concepto) == 'Otro' ? 'selected' : '' }}>Otro</option>
                                            </select>
                                            @error('concepto')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Mes</label>
                                            <div class="form-control bg-light">
                                                {{ DateTime::createFromFormat('!m', $pension->mes)->format('F') }}
                                            </div>
                                            <small class="text-muted">No se puede modificar el período</small>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Año</label>
                                            <div class="form-control bg-light">
                                                {{ $pension->año }}
                                            </div>
                                            <small class="text-muted">No se puede modificar el período</small>
                                        </div>
                                    </div>
                                </div>

                                <hr>

                                <!-- Información Financiera -->
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <h6 class="text-muted mb-3">
                                            <i class="fas fa-dollar-sign me-1"></i>
                                            Información Financiera
                                        </h6>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="valor_base" class="form-label">Valor Base <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text">$</span>
                                                <input type="number" class="form-control @error('valor_base') is-invalid @enderror" 
                                                       id="valor_base" name="valor_base" 
                                                       value="{{ old('valor_base', $pension->valor_base) }}" 
                                                       min="0" step="1000" required>
                                            </div>
                                            @error('valor_base')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="descuentos" class="form-label">Descuentos</label>
                                            <div class="input-group">
                                                <span class="input-group-text">$</span>
                                                <input type="number" class="form-control @error('descuentos') is-invalid @enderror" 
                                                       id="descuentos" name="descuentos" 
                                                       value="{{ old('descuentos', $pension->descuentos) }}" 
                                                       min="0" step="1000">
                                            </div>
                                            @error('descuentos')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="recargos" class="form-label">Recargos</label>
                                            <div class="input-group">
                                                <span class="input-group-text">$</span>
                                                <input type="number" class="form-control @error('recargos') is-invalid @enderror" 
                                                       id="recargos" name="recargos" 
                                                       value="{{ old('recargos', $pension->recargos) }}" 
                                                       min="0" step="1000">
                                            </div>
                                            @error('recargos')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">Valor Total</label>
                                            <div class="input-group">
                                                <span class="input-group-text">$</span>
                                                <input type="text" class="form-control bg-light" id="valorTotal" readonly>
                                            </div>
                                            <small class="text-muted">Calculado automáticamente</small>
                                        </div>
                                    </div>
                                </div>

                                @if($pension->recargo_mora > 0)
                                    <div class="row mb-4">
                                        <div class="col-12">
                                            <div class="alert alert-warning">
                                                <i class="fas fa-exclamation-triangle me-2"></i>
                                                <strong>Recargo por Mora:</strong> ${{ number_format($pension->recargo_mora, 0) }}
                                                @if($pension->fecha_recargo)
                                                    <br><small>Aplicado el {{ \Carbon\Carbon::parse($pension->fecha_recargo)->format('d/m/Y H:i') }}</small>
                                                @endif
                                                <br><small class="text-muted">Este recargo se mantiene hasta que se procese el pago</small>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="fecha_vencimiento" class="form-label">Fecha de Vencimiento <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control @error('fecha_vencimiento') is-invalid @enderror" 
                                                   id="fecha_vencimiento" name="fecha_vencimiento" 
                                                   value="{{ old('fecha_vencimiento', \Carbon\Carbon::parse($pension->fecha_vencimiento)->format('Y-m-d')) }}" 
                                                   required>
                                            @error('fecha_vencimiento')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            @if($pension->isVencida())
                                                <small class="text-danger">
                                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                                    Esta pensión ya está vencida. Cambiar la fecha no eliminará recargos aplicados.
                                                </small>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <hr>

                                <!-- Observaciones -->
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <div class="mb-3">
                                            <label for="observaciones" class="form-label">Observaciones</label>
                                            <textarea class="form-control @error('observaciones') is-invalid @enderror" 
                                                      id="observaciones" name="observaciones" rows="4" 
                                                      maxlength="1000" placeholder="Información adicional sobre esta pensión...">{{ old('observaciones', $pension->observaciones) }}</textarea>
                                            <div class="form-text">
                                                <span id="contadorObservaciones">0</span>/1000 caracteres
                                            </div>
                                            @error('observaciones')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Botones -->
                                <div class="row">
                                    <div class="col-12">
                                        <div class="d-flex justify-content-end gap-2">
                                            <a href="{{ route('pensiones.show', $pension) }}" class="btn btn-secondary">
                                                <i class="fas fa-times"></i> Cancelar
                                            </a>
                                            <button type="submit" class="btn btn-warning">
                                                <i class="fas fa-save"></i> Actualizar Pensión
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Panel de información -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-info-circle me-2"></i>
                                Estado Actual
                            </h5>
                        </div>
                        <div class="card-body text-center">
                            @switch($pension->estado)
                                @case('pendiente')
                                    <i class="fas fa-clock fa-3x text-warning mb-2"></i>
                                    <h5 class="text-warning">PENDIENTE</h5>
                                    @break
                                @case('vencida')
                                    <i class="fas fa-exclamation-triangle fa-3x text-danger mb-2"></i>
                                    <h5 class="text-danger">VENCIDA</h5>
                                    <p class="text-muted">{{ $pension->diasVencida() }} días vencida</p>
                                    @break
                                @case('pagada')
                                    <i class="fas fa-check-circle fa-3x text-success mb-2"></i>
                                    <h5 class="text-success">PAGADA</h5>
                                    @break
                                @case('anulada')
                                    <i class="fas fa-ban fa-3x text-secondary mb-2"></i>
                                    <h5 class="text-secondary">ANULADA</h5>
                                    @break
                            @endswitch

                            <hr>
                            
                            <div class="text-start">
                                <small class="text-muted">Valor Actual:</small>
                                <br>
                                <strong class="h5 text-primary">${{ number_format($pension->valor_total, 0) }}</strong>
                            </div>
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Importante
                            </h5>
                        </div>
                        <div class="card-body">
                            <ul class="small mb-0">
                                <li>Solo se pueden editar pensiones pendientes o vencidas</li>
                                <li>El período (mes/año) no se puede modificar</li>
                                <li>Los recargos por mora ya aplicados se mantienen</li>
                                <li>Los cambios se reflejan inmediatamente en el sistema</li>
                            </ul>
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-calculator me-2"></i>
                                Cálculo
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="small">
                                <strong>Valor Total = </strong>
                                <br>Valor Base + Recargos - Descuentos
                                @if($pension->recargo_mora > 0)
                                    <br>+ Recargo Mora (${{ number_format($pension->recargo_mora, 0) }})
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="application/json" id="pension-data">{!! json_encode(['recargo_mora' => $pension->recargo_mora, 'valor_base' => $pension->valor_base]) !!}</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Elementos del formulario
    const valorBaseInput = document.getElementById('valor_base');
    const descuentosInput = document.getElementById('descuentos');
    const recargosInput = document.getElementById('recargos');
    const valorTotalInput = document.getElementById('valorTotal');
    const observacionesTextarea = document.getElementById('observaciones');
    const contadorObservaciones = document.getElementById('contadorObservaciones');

    // Datos desde servidor (JSON seguro)
    const _pensionDataEl = document.getElementById('pension-data');
    const _pensionData = _pensionDataEl ? JSON.parse(_pensionDataEl.textContent || '{}') : {};
    const recargoMora = parseFloat(_pensionData.recargo_mora) || 0;

    // Función para calcular valor total
    function calcularValorTotal() {
        const valorBase = parseFloat(valorBaseInput.value) || 0;
        const descuentos = parseFloat(descuentosInput.value) || 0;
        const recargos = parseFloat(recargosInput.value) || 0;
        
        const total = valorBase + recargos - descuentos + recargoMora;
        valorTotalInput.value = total.toLocaleString('es-CO');
    }

    // Event listeners para cálculo automático
    [valorBaseInput, descuentosInput, recargosInput].forEach(input => {
        if (input) {
            input.addEventListener('input', calcularValorTotal);
        }
    });

    // Contador de caracteres para observaciones
    if (observacionesTextarea && contadorObservaciones) {
        observacionesTextarea.addEventListener('input', function() {
            contadorObservaciones.textContent = this.value.length;
        });
        
        // Inicial
        contadorObservaciones.textContent = observacionesTextarea.value.length;
    }

    // Calcular valor total inicial
    calcularValorTotal();

    // Validación del formulario
    const form = document.getElementById('formEditarPension');
    if (form) {
        form.addEventListener('submit', function(e) {
            if (!valorBaseInput.value || parseFloat(valorBaseInput.value) <= 0) {
                e.preventDefault();
                alert('El valor base debe ser mayor a cero.');
                valorBaseInput.focus();
                return false;
            }

            // Confirmación si hay cambios significativos
            const valorBaseOriginal = parseFloat(_pensionData.valor_base) || 0;
            const valorBaseNuevo = parseFloat(valorBaseInput.value);
            
            if (Math.abs(valorBaseNuevo - valorBaseOriginal) > valorBaseOriginal * 0.2) {
                if (!confirm('Ha cambiado el valor base significativamente. ¿Está seguro de continuar?')) {
                    e.preventDefault();
                    return false;
                }
            }
        });
    }
});
</script>
@endpush

@push('styles')
<style>
    .avatar {
        flex-shrink: 0;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: #ffc107;
        box-shadow: 0 0 0 0.25rem rgba(255, 193, 7, 0.25);
    }
    
    #valorTotal {
        font-weight: bold;
        color: #0d6efd;
    }
</style>
@endpush
