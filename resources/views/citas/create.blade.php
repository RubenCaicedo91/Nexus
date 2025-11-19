@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-calendar-plus me-2"></i>Nueva Cita
                    </h4>
                    <a href="{{ route('citas.index') }}" class="btn btn-light">
                        <i class="fas fa-arrow-left me-2"></i>Volver al Listado
                    </a>
                </div>

                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Importante:</strong> Complete todos los campos requeridos. Una vez enviada la solicitud, 
                        recibirá confirmación de la fecha y hora asignada por el orientador.
                    </div>

                    <form action="{{ route('citas.store') }}" method="POST">
                        @csrf
                        
                        <div class="row">
                            {{-- Información básica --}}
                            <div class="col-md-6">
                                <div class="card mb-4">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0">
                                            <i class="fas fa-info me-2"></i>Información Básica
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="tipo_cita" class="form-label">Tipo de Cita <span class="text-danger">*</span></label>
                                            <select name="tipo_cita" id="tipo_cita" class="form-select @error('tipo_cita') is-invalid @enderror" required>
                                                <option value="">-- Seleccionar tipo --</option>
                                                @foreach(\App\Models\Cita::TIPOS_CITA as $key => $value)
                                                    <option value="{{ $key }}" {{ old('tipo_cita') == $key ? 'selected' : '' }}>
                                                        {{ $value }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('tipo_cita')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label for="modalidad" class="form-label">Modalidad <span class="text-danger">*</span></label>
                                            <select name="modalidad" id="modalidad" class="form-select @error('modalidad') is-invalid @enderror" required>
                                                <option value="">-- Seleccionar modalidad --</option>
                                                @foreach(\App\Models\Cita::MODALIDADES as $key => $value)
                                                    <option value="{{ $key }}" {{ old('modalidad') == $key ? 'selected' : '' }}>
                                                        {{ $value }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('modalidad')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label for="prioridad" class="form-label">Prioridad <span class="text-danger">*</span></label>
                                            <select name="prioridad" id="prioridad" class="form-select @error('prioridad') is-invalid @enderror" required>
                                                <option value="">-- Seleccionar prioridad --</option>
                                                @foreach(\App\Models\Cita::PRIORIDADES as $key => $value)
                                                    <option value="{{ $key }}" {{ old('prioridad', 'media') == $key ? 'selected' : '' }}>
                                                        {{ $value }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('prioridad')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label for="estudiante_referido_id" class="form-label">Estudiante Referido</label>
                                            <select name="estudiante_referido_id" id="estudiante_referido_id" class="form-select @error('estudiante_referido_id') is-invalid @enderror">
                                                <option value="">-- Seleccionar estudiante (opcional) --</option>
                                                @foreach($estudiantes as $estudiante)
                                                    <option value="{{ $estudiante->id }}" {{ old('estudiante_referido_id') == $estudiante->id ? 'selected' : '' }}>
                                                        {{ $estudiante->name }} - {{ $estudiante->email }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <div class="form-text">
                                                <small class="text-muted">Seleccione el estudiante sobre el cual se tratará la cita (opcional)</small>
                                            </div>
                                            @error('estudiante_referido_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label for="orientador_id" class="form-label">Orientador Preferido</label>
                                            <select name="orientador_id" id="orientador_id" class="form-select @error('orientador_id') is-invalid @enderror">
                                                <option value="">-- Sin preferencia --</option>
                                                @foreach($orientadores as $orientador)
                                                    <option value="{{ $orientador->id }}" {{ old('orientador_id') == $orientador->id ? 'selected' : '' }}>
                                                        {{ $orientador->name }} - {{ $orientador->email }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <div class="form-text">
                                                <small class="text-muted">Puede solicitar un orientador específico (opcional)</small>
                                            </div>
                                            @error('orientador_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Detalles de la cita --}}
                            <div class="col-md-6">
                                <div class="card mb-4">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0">
                                            <i class="fas fa-calendar me-2"></i>Detalles de la Cita
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="motivo" class="form-label">Motivo <span class="text-danger">*</span></label>
                                            <input type="text" name="motivo" id="motivo" 
                                                   class="form-control @error('motivo') is-invalid @enderror" 
                                                   value="{{ old('motivo') }}" 
                                                   placeholder="Breve descripción del motivo de la cita" required>
                                            @error('motivo')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label for="descripcion" class="form-label">Descripción Detallada</label>
                                            <textarea name="descripcion" id="descripcion" 
                                                      class="form-control @error('descripcion') is-invalid @enderror" 
                                                      rows="3" placeholder="Describa con más detalle el tema a tratar...">{{ old('descripcion') }}</textarea>
                                            @error('descripcion')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label for="observaciones_previas" class="form-label">Observaciones Previas</label>
                                            <textarea name="observaciones_previas" id="observaciones_previas" 
                                                      class="form-control @error('observaciones_previas') is-invalid @enderror" 
                                                      rows="2" placeholder="Información adicional que considere relevante...">{{ old('observaciones_previas') }}</textarea>
                                            @error('observaciones_previas')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="fecha_solicitada" class="form-label">Fecha Solicitada <span class="text-danger">*</span></label>
                                                    <input type="date" name="fecha_solicitada" id="fecha_solicitada" 
                                                           class="form-control @error('fecha_solicitada') is-invalid @enderror" 
                                                           value="{{ old('fecha_solicitada', date('Y-m-d', strtotime('+1 day'))) }}" 
                                                           min="{{ date('Y-m-d') }}" required>
                                                    @error('fecha_solicitada')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="hora_solicitada" class="form-label">Hora Solicitada <span class="text-danger">*</span></label>
                                                    <input type="time" name="hora_solicitada" id="hora_solicitada" 
                                                           class="form-control @error('hora_solicitada') is-invalid @enderror" 
                                                           value="{{ old('hora_solicitada', '08:00') }}" required>
                                                    @error('hora_solicitada')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="duracion_estimada" class="form-label">Duración Estimada (minutos) <span class="text-danger">*</span></label>
                                            <select name="duracion_estimada" id="duracion_estimada" class="form-select @error('duracion_estimada') is-invalid @enderror" required>
                                                <option value="">-- Seleccionar duración --</option>
                                                <option value="15" {{ old('duracion_estimada') == '15' ? 'selected' : '' }}>15 minutos</option>
                                                <option value="30" {{ old('duracion_estimada', '30') == '30' ? 'selected' : '' }}>30 minutos</option>
                                                <option value="45" {{ old('duracion_estimada') == '45' ? 'selected' : '' }}>45 minutos</option>
                                                <option value="60" {{ old('duracion_estimada') == '60' ? 'selected' : '' }}>1 hora</option>
                                                <option value="90" {{ old('duracion_estimada') == '90' ? 'selected' : '' }}>1 hora 30 minutos</option>
                                                <option value="120" {{ old('duracion_estimada') == '120' ? 'selected' : '' }}>2 horas</option>
                                            </select>
                                            @error('duracion_estimada')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Detalles de modalidad --}}
                        <div class="row">
                            <div class="col-12">
                                <div class="card mb-4" id="detalles-modalidad" style="display: none;">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0">
                                            <i class="fas fa-map-marker-alt me-2"></i>Detalles de Modalidad
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3" id="campo-lugar" style="display: none;">
                                            <label for="lugar_cita" class="form-label">Lugar de la Cita</label>
                                            <input type="text" name="lugar_cita" id="lugar_cita" 
                                                   class="form-control @error('lugar_cita') is-invalid @enderror" 
                                                   value="{{ old('lugar_cita') }}" 
                                                   placeholder="Especifique el lugar donde se realizará la cita">
                                            @error('lugar_cita')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-3" id="campo-link" style="display: none;">
                                            <label for="link_virtual" class="form-label">Link Virtual</label>
                                            <input type="url" name="link_virtual" id="link_virtual" 
                                                   class="form-control @error('link_virtual') is-invalid @enderror" 
                                                   value="{{ old('link_virtual') }}" 
                                                   placeholder="https://meet.google.com/... o https://zoom.us/...">
                                            <div class="form-text">
                                                <small class="text-muted">El orientador puede proporcionar el link posteriormente</small>
                                            </div>
                                            @error('link_virtual')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label for="instrucciones_adicionales" class="form-label">Instrucciones Adicionales</label>
                                            <textarea name="instrucciones_adicionales" id="instrucciones_adicionales" 
                                                      class="form-control @error('instrucciones_adicionales') is-invalid @enderror" 
                                                      rows="2" placeholder="Cualquier instrucción especial o información adicional...">{{ old('instrucciones_adicionales') }}</textarea>
                                            @error('instrucciones_adicionales')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Botones de acción --}}
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('citas.index') }}" class="btn btn-secondary">
                                        <i class="fas fa-times me-2"></i>Cancelar
                                    </a>
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-save me-2"></i>Solicitar Cita
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modalidadSelect = document.getElementById('modalidad');
    const detallesModalidad = document.getElementById('detalles-modalidad');
    const campoLugar = document.getElementById('campo-lugar');
    const campoLink = document.getElementById('campo-link');

    function actualizarModalidad() {
        const modalidad = modalidadSelect.value;
        
        if (modalidad) {
            detallesModalidad.style.display = 'block';
            
            if (modalidad === 'presencial') {
                campoLugar.style.display = 'block';
                campoLink.style.display = 'none';
                document.getElementById('lugar_cita').required = true;
                document.getElementById('link_virtual').required = false;
            } else if (modalidad === 'virtual') {
                campoLugar.style.display = 'none';
                campoLink.style.display = 'block';
                document.getElementById('lugar_cita').required = false;
                document.getElementById('link_virtual').required = false; // No requerido, puede ser proporcionado después
            } else if (modalidad === 'telefonica') {
                campoLugar.style.display = 'none';
                campoLink.style.display = 'none';
                document.getElementById('lugar_cita').required = false;
                document.getElementById('link_virtual').required = false;
            }
        } else {
            detallesModalidad.style.display = 'none';
        }
    }

    modalidadSelect.addEventListener('change', actualizarModalidad);
    
    // Ejecutar al cargar la página para mantener el estado
    actualizarModalidad();

    // Validación del formulario
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        const modalidad = modalidadSelect.value;
        const lugar = document.getElementById('lugar_cita').value.trim();
        
        if (modalidad === 'presencial' && !lugar) {
            e.preventDefault();
            alert('El lugar de la cita es obligatorio para citas presenciales.');
            document.getElementById('lugar_cita').focus();
            return false;
        }
    });
});
</script>
@endsection