@extends('layouts.app')

@section('title', 'Crear Pensión')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-1">
                        <i class="fas fa-plus-circle text-primary me-2"></i>
                        Crear Nueva Pensión
                    </h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('pensiones.index') }}">Pensiones</a></li>
                            <li class="breadcrumb-item active">Crear</li>
                        </ol>
                    </nav>
                </div>
                <a href="{{ route('pensiones.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Cancelar
                </a>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-form me-2"></i>
                                Información de la Pensión
                            </h5>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('pensiones.store') }}" method="POST" id="formCrearPension">
                                @csrf
                                
                                <!-- Información del Estudiante -->
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <h6 class="text-muted mb-3">
                                            <i class="fas fa-user-graduate me-1"></i>
                                            Información del Estudiante
                                        </h6>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="estudiante_id" class="form-label">Estudiante <span class="text-danger">*</span></label>
                                            <select class="form-select @error('estudiante_id') is-invalid @enderror" 
                                                    id="estudiante_id" name="estudiante_id" required>
                                                <option value="">Seleccionar estudiante</option>
                                                @foreach($estudiantes as $estudiante)
                                                    <option value="{{ $estudiante->id }}" {{ old('estudiante_id') == $estudiante->id ? 'selected' : '' }}>
                                                        {{ $estudiante->name }} - {{ $estudiante->email }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('estudiante_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Información del Curso</label>
                                            <div id="infoCurso" class="form-control bg-light" style="min-height: 38px;">
                                                <span class="text-muted">Seleccione un estudiante</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <hr>

                                <!-- Información de la Pensión -->
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
                                                <option value="Pensión Escolar" {{ old('concepto') == 'Pensión Escolar' ? 'selected' : '' }}>Pensión Escolar</option>
                                                <option value="Matrícula" {{ old('concepto') == 'Matrícula' ? 'selected' : '' }}>Matrícula</option>
                                                <option value="Seguro Estudiantil" {{ old('concepto') == 'Seguro Estudiantil' ? 'selected' : '' }}>Seguro Estudiantil</option>
                                                <option value="Material Didáctico" {{ old('concepto') == 'Material Didáctico' ? 'selected' : '' }}>Material Didáctico</option>
                                                <option value="Transporte" {{ old('concepto') == 'Transporte' ? 'selected' : '' }}>Transporte</option>
                                                <option value="Alimentación" {{ old('concepto') == 'Alimentación' ? 'selected' : '' }}>Alimentación</option>
                                                <option value="Otro" {{ old('concepto') == 'Otro' ? 'selected' : '' }}>Otro</option>
                                            </select>
                                            @error('concepto')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="mes" class="form-label">Mes <span class="text-danger">*</span></label>
                                            <select class="form-select @error('mes') is-invalid @enderror" id="mes" name="mes" required>
                                                <option value="">Seleccionar mes</option>
                                                @for($i = 1; $i <= 12; $i++)
                                                    <option value="{{ $i }}" {{ old('mes', date('n')) == $i ? 'selected' : '' }}>
                                                        {{ DateTime::createFromFormat('!m', $i)->format('F') }}
                                                    </option>
                                                @endfor
                                            </select>
                                            @error('mes')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="año" class="form-label">Año <span class="text-danger">*</span></label>
                                            <select class="form-select @error('año') is-invalid @enderror" id="año" name="año" required>
                                                @for($year = date('Y'); $year <= date('Y') + 1; $year++)
                                                    <option value="{{ $year }}" {{ old('año', date('Y')) == $year ? 'selected' : '' }}>
                                                        {{ $year }}
                                                    </option>
                                                @endfor
                                            </select>
                                            @error('año')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
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
                                                       id="valor_base" name="valor_base" value="{{ old('valor_base') }}" 
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
                                                       id="descuentos" name="descuentos" value="{{ old('descuentos', 0) }}" 
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
                                                       id="recargos" name="recargos" value="{{ old('recargos', 0) }}" 
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

                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="fecha_vencimiento" class="form-label">Fecha de Vencimiento <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control @error('fecha_vencimiento') is-invalid @enderror" 
                                                   id="fecha_vencimiento" name="fecha_vencimiento" 
                                                   value="{{ old('fecha_vencimiento', date('Y-m-d', strtotime('+30 days'))) }}" required>
                                            @error('fecha_vencimiento')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
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
                                                      id="observaciones" name="observaciones" rows="3" 
                                                      maxlength="1000" placeholder="Información adicional sobre esta pensión...">{{ old('observaciones') }}</textarea>
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
                                            <a href="{{ route('pensiones.index') }}" class="btn btn-secondary">
                                                <i class="fas fa-times"></i> Cancelar
                                            </a>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save"></i> Crear Pensión
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Panel de ayuda -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-info-circle me-2"></i>
                                Información de Ayuda
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <h6 class="text-primary">
                                    <i class="fas fa-lightbulb me-1"></i>
                                    Consejos
                                </h6>
                                <ul class="small">
                                    <li>El valor total se calcula automáticamente</li>
                                    <li>Los descuentos reducen el valor base</li>
                                    <li>Los recargos se suman al valor base</li>
                                    <li>La fecha de vencimiento determina cuando se aplican recargos por mora</li>
                                </ul>
                            </div>

                            <div class="mb-3">
                                <h6 class="text-warning">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    Importante
                                </h6>
                                <ul class="small">
                                    <li>No se pueden crear pensiones duplicadas para el mismo estudiante, mes y año</li>
                                    <li>El estudiante debe tener una matrícula activa</li>
                                    <li>Los valores se manejan en pesos colombianos</li>
                                </ul>
                            </div>

                            <div class="mb-0">
                                <h6 class="text-info">
                                    <i class="fas fa-calculator me-1"></i>
                                    Cálculo Automático
                                </h6>
                                <div class="small">
                                    <strong>Valor Total = </strong>
                                    <br>Valor Base + Recargos - Descuentos
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Información del estudiante seleccionado -->
                    <div class="card mt-3" id="cardInfoEstudiante" style="display: none;">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-user-graduate me-2"></i>
                                Información del Estudiante
                            </h5>
                        </div>
                        <div class="card-body" id="infoEstudianteContent">
                            <!-- Se llena dinámicamente con JavaScript -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')

<script type="application/json" id="estudiantes-data">{!! json_encode($estudiantesDataArr ?? []) !!}</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Elementos del formulario
    const estudianteSelect = document.getElementById('estudiante_id');
    const valorBaseInput = document.getElementById('valor_base');
    const descuentosInput = document.getElementById('descuentos');
    const recargosInput = document.getElementById('recargos');
    const valorTotalInput = document.getElementById('valorTotal');
    const observacionesTextarea = document.getElementById('observaciones');
    const contadorObservaciones = document.getElementById('contadorObservaciones');
    const infoCursoDiv = document.getElementById('infoCurso');
    const cardInfoEstudiante = document.getElementById('cardInfoEstudiante');
    const infoEstudianteContent = document.getElementById('infoEstudianteContent');

    // Datos de estudiantes (inyectados desde el controlador)
    const estudiantesData = JSON.parse(document.getElementById('estudiantes-data').textContent || '[]');

    // Función para calcular valor total
    function calcularValorTotal() {
        const valorBase = parseFloat(valorBaseInput.value) || 0;
        const descuentos = parseFloat(descuentosInput.value) || 0;
        const recargos = parseFloat(recargosInput.value) || 0;
        
        const total = valorBase + recargos - descuentos;
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

    // Manejo de selección de estudiante
    if (estudianteSelect) {
        estudianteSelect.addEventListener('change', function() {
            const estudianteId = this.value;
            
            if (estudianteId && estudiantesData[estudianteId]) {
                const estudiante = estudiantesData[estudianteId];
                
                // Mostrar información del curso
                if (estudiante.matricula) {
                    infoCursoDiv.innerHTML = `
                        <strong>Curso:</strong> ${estudiante.matricula.curso_nombre}<br>
                        <strong>Grado:</strong> ${estudiante.matricula.grado}<br>
                        <strong>Acudiente:</strong> ${estudiante.matricula.acudiente}
                    `;
                } else {
                    infoCursoDiv.innerHTML = '<span class="text-danger">No tiene matrícula activa</span>';
                }

                // Mostrar información detallada del estudiante
                infoEstudianteContent.innerHTML = `
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar bg-primary rounded-circle me-3" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-user text-white"></i>
                        </div>
                        <div>
                            <h6 class="mb-1">${estudiante.nombre}</h6>
                            <small class="text-muted">${estudiante.email}</small>
                        </div>
                    </div>
                    ${estudiante.matricula ? `
                        <div class="mb-2">
                            <strong>Curso:</strong> ${estudiante.matricula.curso_nombre}
                        </div>
                        <div class="mb-2">
                            <strong>Grado:</strong> <span class="badge bg-info">${estudiante.matricula.grado}</span>
                        </div>
                        <div class="mb-0">
                            <strong>Acudiente:</strong> ${estudiante.matricula.acudiente}
                        </div>
                    ` : `
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            Este estudiante no tiene matrícula activa
                        </div>
                    `}
                `;
                
                cardInfoEstudiante.style.display = 'block';
            } else {
                infoCursoDiv.innerHTML = '<span class="text-muted">Seleccione un estudiante</span>';
                cardInfoEstudiante.style.display = 'none';
            }
        });
    }

    // Calcular valor total inicial
    calcularValorTotal();

    // Validación del formulario
    const form = document.getElementById('formCrearPension');
    if (form) {
        form.addEventListener('submit', function(e) {
            const estudianteId = estudianteSelect.value;
            
            if (estudianteId && estudiantesData[estudianteId] && !estudiantesData[estudianteId].matricula) {
                e.preventDefault();
                alert('El estudiante seleccionado no tiene matrícula activa. No se puede crear la pensión.');
                return false;
            }

            if (!valorBaseInput.value || parseFloat(valorBaseInput.value) <= 0) {
                e.preventDefault();
                alert('El valor base debe ser mayor a cero.');
                valorBaseInput.focus();
                return false;
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
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }
    
    #valorTotal {
        font-weight: bold;
        color: #0d6efd;
    }
</style>
@endpush
